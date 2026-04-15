# Panduan Setup Cron Job — Hosting cPanel (olen6374)

## Path Server
```
/home/olen6374/public_html/app.oleh2indonesia.com
```

---

## 🚀 Arsitektur Baru (Setelah Optimasi)

| | Sebelum | Sesudah |
|---|---|---|
| Job per run | N jobs (1 per SKU) | M jobs (1 per akun) |
| DB query per run | N queries (1 per SKU) | M queries (getStockBulk) |
| Queue penuh? | Ya — 10.000+ jobs | Tidak — 3–5 jobs saja |
| Estimasi waktu (5000 SKU, 3 akun) | 4–6 jam | 15–30 menit |

---

## ⚙️ Cron Jobs yang Diperlukan

### 1. Queue Worker (TikTok + Shopee) — Jalankan setiap 1 menit
Satu cron untuk memproses **kedua queue** (`tiktok-inventory` dan `shopee-inventory`) secara bergantian.

Gunakan **`queue-worker.php`** (bukan `php artisan` langsung) karena di shared hosting cPanel, binary `php` di cron PATH sering bukan PHP 8.x yang benar.

```bash
* * * * * curl -s "https://app.oleh2indonesia.com/queue-worker.php?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
```

> **Cara kerja:**
> - Setiap menit curl memanggil `queue-worker.php`
> - File tersebut langsung kirim response ke curl (< 1 detik), lalu lanjut proses **1 job** dari queue `tiktok-inventory,shopee-inventory` di background
> - Laravel memilih job dengan prioritas: TikTok lebih dulu, Shopee berikutnya
> - `--max-jobs=1` → 1 job per panggilan, aman untuk timeout shared hosting

> ⚠️ **Penyebab job Shopee stuck sebelumnya:** file `queue-worker.php` hanya memproses `tiktok-inventory`. Sudah diperbaiki — sekarang memproses kedua queue sekaligus.

---

### 2. Dispatch Stock Sync — Setiap 15 menit
Endpoint yang mendispatch `SyncAccountInventoryJob` (1 job per akun).

```bash
*/15 * * * * curl -s "https://app.oleh2indonesia.com/stock/cron-sync-all?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
```

> Guard di dalam cron endpoint akan **skip** jika jobs sebelumnya belum selesai.  
> Tidak perlu khawatir overlap job.

---

### 3. Product Sync — 1x sehari (jam 03:00)
Sinkronisasi daftar produk dari TikTok API ke tabel `produk_saya`.  
Daftar produk jarang berubah, tidak perlu setiap 5 menit.

```bash
0 3 * * * curl -s "https://app.oleh2indonesia.com/stock/cron-sync-all?secret=kiosq_stock_sync_2026&sync_products=1" > /dev/null 2>&1
```

---

### 4. Token Refresh — Setiap jam (opsional)
Refresh access token sebelum expired.

```bash
0 * * * * curl -s "https://app.oleh2indonesia.com/tiktok/cron-refresh-token?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
```

---

## 📋 Ringkasan Semua Cron (Copy-Paste ke cPanel)

```
* * * * * curl -s "https://app.oleh2indonesia.com/queue-worker.php?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
*/15 * * * * curl -s "https://app.oleh2indonesia.com/stock/cron-sync-all?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
0 3 * * * curl -s "https://app.oleh2indonesia.com/stock/cron-sync-all?secret=kiosq_stock_sync_2026&sync_products=1" > /dev/null 2>&1
0 * * * * curl -s "https://app.oleh2indonesia.com/tiktok/cron-refresh-token?secret=kiosq_stock_sync_2026" > /dev/null 2>&1
```

---

## ❓ FAQ

### Q: Kenapa sekarang 1 job per akun, bukan 1 job per SKU?

**A:** Job lama (`UpdateTiktokInventoryJob`) dispatch 1 job per SKU.  
Untuk 10.000 SKU → 10.000 jobs masuk antrian sekaligus.  
Queue worker memproses 1 job per detik → butuh 2,7+ jam hanya untuk habiskan antrian.

Job baru (`SyncAccountInventoryJob`) dispatch 1 job per akun.  
Di dalam 1 job:
- `getStockBulk()` → 1 query DB POS untuk **semua** SKU sekaligus
- Loop `updateInventory()` → N HTTP request ke TikTok, dengan jeda 100ms

Hasilnya: 3 akun = 3 jobs → berjalan paralel → selesai 5–15 menit.

---

### Q: Boleh hapus `run-queue` cron lama?

**A:** Ya. Dulu cron memanggil `curl /stock/run-queue` yang menjalankan  
`Artisan::call('queue:work', ['--max-time' => 55])` via HTTP.  
Ini terbatas karena HTTP request timeout.

Sekarang ganti dengan cron langsung:
```bash
* * * * * cd /home/olen6374/... && php artisan queue:work --max-time=55
```
Ini lebih stabil, tidak bergantung HTTP timeout.

---

### Q: Update stok perlu tarik data dari TikTok dulu?

**A:** **Tidak.** Alur yang benar (dan lebih efisien):
```
POS DB (source of truth)
    ↓ getStockBulk()
SyncAccountInventoryJob
    ↓ updateInventory() per SKU
TikTok API
```

Kita **tidak pernah** perlu tarik stok dari TikTok untuk update inventory.  
TikTok Seller Center hanya sebagai **tujuan**, bukan sumber data stok.

Yang perlu disync dari TikTok hanyalah **daftar produk** (product_id, sku_id)  
agar kita tahu ke mana menyimpan stok — dan ini cukup 1x/hari.

---

### Q: Bagaimana cara cek apakah queue worker berjalan?

**A:** Buka browser:
```
https://app.oleh2indonesia.com/stock/cron-sync-all?secret=kiosq_stock_sync_2026
```
Jika ada jobs yang sedang berjalan, endpoint mengembalikan:
```json
{
  "status": "skipped_jobs_still_pending",
  "jobs_pending": 3
}
```
Artinya 3 batch jobs sedang diproses worker — normal.

---

## 🔑 Secrets
- `stock_sync_secret = kiosq_stock_sync_2026`
- Ganti di `config/app.php` jika perlu lebih aman
