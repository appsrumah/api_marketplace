<?php

namespace App\Console\Commands;

use App\Models\AccountShopTiktok;
use App\Services\TiktokApiService;
use Illuminate\Console\Command;

class RegisterTiktokWebhooks extends Command
{
    protected $signature = 'tiktok:register-webhooks
                            {--account= : ID akun spesifik (opsional, default semua akun aktif)}
                            {--events=  : Event types comma-separated (default: ORDER_STATUS_CHANGE,NEW_MESSAGE_LISTENER)}';

    protected $description = 'Daftarkan webhook URL ke TikTok Shop API untuk semua akun aktif';

    /* ── Default events yang didaftarkan ─────────────────────────── */
    private const DEFAULT_EVENTS = [
        'ORDER_STATUS_CHANGE',
        'NEW_MESSAGE_LISTENER',
    ];

    public function handle(TiktokApiService $tiktokService): int
    {
        $webhookUrl = rtrim(config('app.url'), '/') . '/webhooks/tiktok';

        $this->info("🔗 Webhook URL: {$webhookUrl}");
        $this->newLine();

        // ── Parse event types ────────────────────────────────────
        $eventsInput = $this->option('events');
        $events = $eventsInput
            ? array_map('trim', explode(',', $eventsInput))
            : self::DEFAULT_EVENTS;

        $this->info('📋 Events yang akan didaftarkan:');
        foreach ($events as $event) {
            $this->line("   • {$event}");
        }
        $this->newLine();

        // ── Ambil akun ───────────────────────────────────────────
        $accountId = $this->option('account');
        $accounts = $accountId
            ? AccountShopTiktok::where('id', $accountId)->where('status', 'active')->get()
            : AccountShopTiktok::where('status', 'active')
            ->whereNotNull('shop_cipher')
            ->whereNotNull('access_token')
            ->get();

        if ($accounts->isEmpty()) {
            $this->error('❌ Tidak ada akun aktif ditemukan.');
            return self::FAILURE;
        }

        $this->info("📦 Jumlah akun: {$accounts->count()}");
        $this->newLine();

        $successCount = 0;
        $failCount    = 0;

        foreach ($accounts as $account) {
            $shopLabel = $account->shop_name ?? $account->seller_name;
            $this->info("── {$shopLabel} (ID: {$account->id}) ──");

            // Cek token expired
            if ($account->isTokenExpired()) {
                $this->warn("   ⚠️ Token expired, skip. Jalankan cron refresh token dulu.");
                $failCount += count($events);
                continue;
            }

            foreach ($events as $eventType) {
                try {
                    $tiktokService->updateShopWebhook(
                        accessToken: $account->access_token,
                        shopCipher: $account->shop_cipher,
                        address: $webhookUrl,
                        eventType: $eventType,
                    );

                    $this->line("   ✅ {$eventType} → registered");
                    $successCount++;
                } catch (\Throwable $e) {
                    $this->error("   ❌ {$eventType} → {$e->getMessage()}");
                    $failCount++;
                }

                usleep(300_000); // 300ms jeda
            }

            $this->newLine();
        }

        // ── Summary ──────────────────────────────────────────────
        $this->newLine();
        $this->info("═══════════════════════════════════");
        $this->info("✅ Berhasil: {$successCount}");
        if ($failCount > 0) {
            $this->error("❌ Gagal: {$failCount}");
        }
        $this->info("═══════════════════════════════════");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
