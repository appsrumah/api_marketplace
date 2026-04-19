# Panduan Stock Sync (Migration & Runbook)

Dokumen ini menjelaskan langkah-langkah menjalankan migrasi, rollback, konfigurasi cache/queue, dan cara uji untuk fitur sinkronisasi stok (diff-only + batch).

## Ringkasan Perubahan
- Menambahkan kolom `last_pushed_stock` (int, nullable) dan `last_pushed_at` (timestamp, nullable) di tabel `produk_saya`.
- Job sinkronisasi sekarang membandingkan stok POS dengan `last_pushed_stock` dan hanya mengirim perubahan.
- TikTok: ada `batchUpdateInventory()` untuk request concurrent.
- Locking: memakai MySQL `GET_LOCK()`/`RELEASE_LOCK()` sehingga Redis tidak wajib.

## File penting
- Migration: `database/migrations/2026_04_19_120000_add_last_pushed_to_produk_saya.php`
- Job TikTok: `app/Jobs/SyncAccountInventoryJob.php`
- Job Shopee: `app/Jobs/SyncShopeeInventoryJob.php`
- Controller dashboard: `app/Http/Controllers/StockController.php`
- Dry-run command: `app/Console/Commands/StockDryRunCommand.php`

## Langkah migrasi (production)
1. Tarik perubahan ke server dan pastikan dependency terinstall.
2. Backup database sebelum menjalankan migration.
3. Jalankan migrasi:

```bash
php artisan migrate --force
```

Catatan: migration yang ditambahkan melakukan backfill `last_pushed_stock` dari `quantity` sehingga nilai awal konsisten.

## Rollback migration
Jika perlu rollback satu langkah (terakhir):

```bash
php artisan migrate:rollback --step=1
```

Atau rollback spesifik migration (hati-hati): rollback akan menghapus kolom dan data `last_pushed_*`.

## Konfigurasi cache & queue
- Direkomendasikan `QUEUE_CONNECTION=database` (saat ini memakai database queue).
- Karena implementasi lock menggunakan MySQL `GET_LOCK`, Redis tidak wajib. Jika ingin gunakan Redis (untuk lock/Cache::lock):
  - Pasang PHP Redis extension atau `predis/predis`.
  - Set `CACHE_DRIVER=redis` di `.env`.

Jika memilih `database` cache store (pilihan aman tanpa Redis):

```bash
php artisan cache:table
php artisan migrate --force
```

## Supervisor / Worker recommendations
- Sistem memproses job per akun (1 job per akun). Rekomendasi awal untuk production kecil–sedang:
  - 2 worker proses (`php artisan queue:work --queue=tiktok-inventory --tries=2 --timeout=600 --memory=256`) untuk TikTok
  - 1 worker untuk `shopee-inventory` (karena jeda rate-limit per item)
  - Atur `supervisor` atau systemd sesuai kebutuhan, gunakan `queue:restart` saat deploy.

Contoh perintah manajerial untuk testing manual (1 job):

```bash
# dispatch sync for account id 123 (HTTP endpoint)
curl -s "https://your-host/stock/123/sync"

# run worker once (safe for HTTP cron)
php artisan queue:work --queue=tiktok-inventory,shopee-inventory --max-jobs=1 --timeout=540 --tries=2
```

## Cara uji (safe)
1. Dry-run (tanpa mengubah DB atau memanggil API):

```bash
php artisan stock:dry-run
php artisan stock:dry-run --accountId=123 --limit=50
```

2. Jalankan sync 1 akun via endpoint (membuat 1 job):

```bash
curl -s "https://your-host/stock/123/sync"
```

3. Periksa progress Live Monitor di UI `/stock` atau polling endpoint `GET /stock/sync-progress`.

## Hal yang perlu dicek setelah migrasi
- Pastikan kolom `last_pushed_stock` terisi (migration backfill). Jika tidak, jalankan query backfill manual:

```sql
UPDATE produk_saya SET last_pushed_stock = quantity WHERE last_pushed_stock IS NULL;
```

- Pastikan user DB memiliki hak untuk `GET_LOCK()` — biasanya tersedia secara default.

## Catatan Operasional
- `GET_LOCK()` bersifat per-connection dan menggunakan nama lock `stock_sync_{accountId}_{id_outlet}`; collision akan menyebabkan job dilewati dan progress cache akan menandai `skipped`.
- Jika Anda ingin menggunakan Redis locks (lebih cepat untuk high-concurrency), pasang ekstensi Redis dan ubah `CACHE_DRIVER=redis`.

## Troubleshooting singkat
- Jika `Class 'Redis' not found` saat menjalankan `php artisan tinker`, berarti PHP Redis extension belum terpasang. Gunakan `pecl install redis` atau pasang paket OS yang sesuai, atau gunakan `predis/predis`.
- Jika after-migration jobs banyak gagal, periksa `failed_jobs` table dan logs `storage/logs/laravel.log`.

---
Dokumen ini dapat diperluas dengan contoh konfigurasi Supervisor dan snippet `systemd` jika Anda butuh. Saya bisa tambahkan itu jika diinginkan.
