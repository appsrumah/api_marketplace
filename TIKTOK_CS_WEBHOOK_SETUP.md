# TikTok Customer Service Webhook — Setup Guide

## Daftar Isi
1. [Arsitektur & Flow](#arsitektur--flow)
2. [Menjalankan Migration](#menjalankan-migration)
3. [Konfigurasi Environment](#konfigurasi-environment)
4. [Mendaftarkan Webhook di TikTok Partner Center](#mendaftarkan-webhook-di-tiktok-partner-center)
5. [Testing Webhook Lokal (ngrok)](#testing-webhook-lokal-ngrok)
6. [Integrasi dengan Omnichannel](#integrasi-dengan-omnichannel)
7. [Artisan Commands](#artisan-commands)
8. [Running Tests](#running-tests)
9. [Troubleshooting](#troubleshooting)

---

## Arsitektur & Flow

```
TikTok Server                     Laravel App
     │                                │
     │  POST /webhooks/tiktok/cs      │
     │───────────────────────────────►│
     │                                ├─ Verify signature
     │  ◄── 200 OK (< 3 detik) ──────┤  Save raw payload to webhook_logs
     │                                ├─ Dispatch ProcessTikTokWebhookJob
     │                                │
     │                          [Queue Worker]
     │                                ├─ Type 13 → processNewConversation()
     │                                │    ├─ Upsert TikTokConversation
     │                                │    └─ Dispatch NewConversationReceived event
     │                                │
     │                                ├─ Type 14 → processNewMessage()
     │                                │    ├─ Upsert TikTokMessage
     │                                │    └─ Dispatch NewMessageReceived event
     │                                │
     │                          [Event Listeners]
     │                                ├─ SendAgentNotification (activity log)
     │                                └─ Broadcasting (realtime ke dashboard agent)
```

### Komponen yang Dibuat

| File | Deskripsi |
|------|-----------|
| `config/tiktok_cs.php` | Konfigurasi webhook, API, queue, logging |
| `app/Http/Controllers/TikTokWebhookController.php` | Menerima webhook POST dari TikTok |
| `app/Services/TikTokCustomerService.php` | Business logic: proses event, sync, kirim pesan |
| `app/Jobs/ProcessTikTokWebhookJob.php` | Queue job untuk async processing |
| `app/Models/TikTokConversation.php` | Model percakapan |
| `app/Models/TikTokMessage.php` | Model pesan |
| `app/Models/TikTokWebhookLog.php` | Model log webhook (debugging) |
| `app/Events/NewConversationReceived.php` | Event broadcast percakapan baru |
| `app/Events/NewMessageReceived.php` | Event broadcast pesan baru |
| `app/Listeners/SendAgentNotification.php` | Listener: activity log & notifikasi agent |
| `app/Console/Commands/SyncTikTokConversations.php` | Artisan command sync percakapan |
| `database/migrations/*_create_tiktok_conversations_table.php` | Migration conversations |
| `database/migrations/*_create_tiktok_messages_table.php` | Migration messages |
| `database/migrations/*_create_tiktok_webhook_logs_table.php` | Migration webhook logs |
| `tests/Unit/TikTokWebhookTest.php` | Unit tests |

---

## Menjalankan Migration

```bash
php artisan migrate
```

Ini akan membuat 3 tabel baru:
- `tiktok_conversations`
- `tiktok_messages`
- `tiktok_webhook_logs`

---

## Konfigurasi Environment

Tambahkan ke file `.env`:

```env
# === TikTok Customer Service Webhook ===
TIKTOK_CS_WEBHOOK_SECRET=           # Secret dari TikTok Partner Center
TIKTOK_CS_VERIFY_SIGNATURE=false    # Set true di production
TIKTOK_CS_API_TIMEOUT=15
TIKTOK_CS_CONNECT_TIMEOUT=5
TIKTOK_CS_QUEUE=default
TIKTOK_CS_QUEUE_TRIES=3
TIKTOK_CS_LOG_PAYLOAD=true
TIKTOK_CS_LOG_RETENTION=30
```

> **Catatan:** `TIKTOK_APP_KEY`, `TIKTOK_APP_SECRET`, dan `TIKTOK_API_BASE` sudah ada di `.env` dan digunakan bersama dengan TikTokApiService yang existing.

---

## Mendaftarkan Webhook di TikTok Partner Center

### Langkah-langkah:

1. **Login** ke [TikTok Shop Partner Center](https://partner.tiktokshop.com/)
2. Masuk ke **App Management** → pilih aplikasi Anda
3. Buka tab **Webhook** atau **Event Subscription**
4. Klik **Add Webhook URL**
5. Isi:
   - **Webhook URL:** `https://yourdomain.com/webhooks/tiktok/customer-service`
   - **Event Type:** Pilih event `Customer Service` (type 13 & 14)
6. TikTok akan menampilkan **Webhook Secret** — salin dan masukkan ke `.env` sebagai `TIKTOK_CS_WEBHOOK_SECRET`
7. Klik **Verify** — TikTok akan mengirim request POST ke URL webhook Anda
8. Pastikan server merespon **200 OK**
9. Setelah verifikasi berhasil, webhook akan aktif

### Event yang didukung:
| Type | Nama | Deskripsi |
|------|------|-----------|
| 13 | NEW_CONVERSATION | Percakapan baru dimulai / agent join-leave |
| 14 | NEW_MESSAGE | Pesan baru dari buyer/agent/system/robot |

---

## Testing Webhook Lokal (ngrok)

### 1. Install ngrok

```bash
# Download dari https://ngrok.com/download
# Atau via chocolatey (Windows):
choco install ngrok
```

### 2. Jalankan Laravel Development Server

```bash
php artisan serve --port=8000
```

### 3. Jalankan ngrok

```bash
ngrok http 8000
```

ngrok akan memberikan URL seperti: `https://abc123.ngrok-free.app`

### 4. Daftarkan URL di TikTok Partner Center

Gunakan: `https://abc123.ngrok-free.app/webhooks/tiktok/customer-service`

### 5. Matikan verifikasi signature saat testing

Di `.env`:
```env
TIKTOK_CS_VERIFY_SIGNATURE=false
```

### 6. Test Manual dengan curl

```bash
curl -X POST https://abc123.ngrok-free.app/webhooks/tiktok/customer-service \
  -H "Content-Type: application/json" \
  -d '{
    "type": 14,
    "shop_id": "YOUR_SHOP_ID",
    "data": {
      "conversation_id": "conv_test_001",
      "message_id": "msg_test_001",
      "sender": {"type": "BUYER", "uid": "buyer_test"},
      "content": {"type": "text", "text": {"text": "Halo, ready kak?"}},
      "create_time": 1712000000
    }
  }'
```

### 7. Jalankan Queue Worker

```bash
php artisan queue:work --queue=default --tries=3
```

### 8. Alternatif: Serveo (tanpa install)

```bash
ssh -R 80:localhost:8000 serveo.net
```

---

## Integrasi dengan Omnichannel

### Multi-Shop Support

Setiap webhook payload mengandung `shop_id` yang digunakan untuk resolve ke `account_shop_tiktok`. Semua data percakapan dan pesan terikat ke `account_id`, sehingga mendukung multiple TikTok shop dalam 1 aplikasi.

### Menampilkan Chat di Dashboard Agent

Data tersedia melalui model relationships:

```php
// Ambil semua percakapan aktif untuk akun user login
$conversations = TikTokConversation::forUser()
    ->active()
    ->withUnread()
    ->with('latestMessage')
    ->latest('last_message_at')
    ->paginate(25);

// Ambil pesan dalam 1 percakapan
$messages = $conversation->messages()
    ->latest('tiktok_created_at')
    ->paginate(50);
```

### Membalas Pesan dari Dashboard

```php
use App\Services\TikTokCustomerService;

$csService = app(TikTokCustomerService::class);

// Kirim text
$csService->sendMessage($account, 'conv_id_xxx', 'Terima kasih, barang ready kak!');

// Kirim gambar
$csService->sendMessage($account, 'conv_id_xxx', 'https://example.com/photo.jpg', 'image');
```

### Realtime Notification (Broadcasting)

Event `NewMessageReceived` sudah implement `ShouldBroadcast`. Di sisi frontend:

```javascript
// Menggunakan Laravel Echo
Echo.channel('tiktok-cs.account.{accountId}')
    .listen('.message.new', (data) => {
        console.log('New message:', data);
        // Update chat UI
        // Show notification badge
    });

Echo.channel('tiktok-cs.account.{accountId}')
    .listen('.conversation.new', (data) => {
        console.log('New conversation:', data);
        // Refresh conversation list
    });
```

### Menambahkan Marketplace Lain (Shopee, Tokopedia, dll)

Arsitektur ini didesain modular. Untuk marketplace lain:

1. Buat webhook controller baru (misal `ShopeeWebhookController`)
2. Buat service class terpisah (misal `ShopeeCustomerService`)
3. **Opsional:** Buat tabel terpisah atau gunakan tabel yang sama dengan kolom `platform`
4. Tambahkan route: `POST /webhooks/shopee/customer-service`
5. Event dan listener bisa di-reuse atau dibuat baru

---

## Artisan Commands

### Sync Percakapan dari TikTok API

```bash
# Sync semua akun aktif
php artisan tiktok:sync-conversations

# Sync 1 akun saja
php artisan tiktok:sync-conversations --account=5

# Dry run (lihat info tanpa sync)
php artisan tiktok:sync-conversations --dry-run

# Hapus webhook logs lama
php artisan tiktok:sync-conversations --prune-logs
```

### Setup Cron Job (cPanel / Server)

Tambahkan ke crontab untuk sync periodik:

```cron
# Sync conversations setiap 30 menit
*/30 * * * * cd /path/to/project && php artisan tiktok:sync-conversations >> /dev/null 2>&1

# Prune logs setiap hari jam 3 pagi
0 3 * * * cd /path/to/project && php artisan tiktok:sync-conversations --prune-logs >> /dev/null 2>&1
```

---

## Running Tests

```bash
# Jalankan semua test
php artisan test

# Jalankan hanya test webhook TikTok
php artisan test --filter=TikTokWebhookTest

# Jalankan test spesifik
php artisan test --filter="it_processes_new_text_message"
```

### Test yang tersedia:

| Test | Deskripsi |
|------|-----------|
| `it_verifies_valid_webhook_signature` | Signature HMAC-SHA256 valid |
| `it_rejects_invalid_webhook_signature` | Signature tidak valid ditolak |
| `it_allows_request_when_secret_not_configured` | Skip verifikasi jika secret kosong |
| `webhook_endpoint_returns_200_and_dispatches_job` | Endpoint return 200 + dispatch job |
| `webhook_with_invalid_signature_returns_200_but_no_job` | Invalid sig → 200 tapi no job |
| `it_processes_new_conversation_event` | Type 13 → simpan conversation |
| `it_processes_new_text_message` | Type 14 text → simpan message |
| `it_processes_new_image_message` | Type 14 image → simpan URL |
| `it_processes_new_product_card_message` | Type 14 product_card → simpan JSON |
| `it_handles_duplicate_messages_idempotently` | Duplikat tidak bikin record baru |
| `it_auto_creates_conversation_on_new_message` | Auto-create conversation jika belum ada |

---

## Troubleshooting

### Webhook tidak diterima
1. Pastikan URL benar: `https://yourdomain.com/webhooks/tiktok/customer-service`
2. Pastikan CSRF exception terdaftar di `bootstrap/app.php`
3. Cek `storage/logs/laravel.log` untuk error

### Queue job tidak berjalan
1. Pastikan queue worker aktif: `php artisan queue:work`
2. Cek tabel `jobs` di database
3. Cek `tiktok_webhook_logs` — kolom `process_status` harus berubah dari `pending`

### Signature verification gagal
1. Pastikan `TIKTOK_CS_WEBHOOK_SECRET` di `.env` sesuai dengan yang di TikTok Partner Center
2. Untuk development, set `TIKTOK_CS_VERIFY_SIGNATURE=false`

### Shop ID tidak terdeteksi
1. Pastikan `shop_id` di tabel `account_shop_tiktok` terisi dan cocok
2. Cek raw payload di `tiktok_webhook_logs` untuk melihat format yang dikirim TikTok
