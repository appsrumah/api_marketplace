<?php

namespace App\Console\Commands;

use App\Models\AccountShopTiktok;
use App\Models\TikTokConversation;
use App\Models\TikTokWebhookLog;
use App\Services\TikTokCustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTikTokConversations extends Command
{
    /**
     * Jalankan: php artisan tiktok:sync-conversations
     * Untuk 1 akun: php artisan tiktok:sync-conversations --account=5
     * Dry run: php artisan tiktok:sync-conversations --dry-run
     * Prune logs lama: php artisan tiktok:sync-conversations --prune-logs
     */
    protected $signature = 'tiktok:sync-conversations
                            {--account=  : ID account_shop_tiktok tertentu (opsional)}
                            {--dry-run   : Tampilkan info tanpa melakukan sync}
                            {--prune-logs : Hapus webhook logs lama sesuai retensi config}';

    protected $description = 'Sinkronisasi percakapan Customer Service dari TikTok API ke database lokal';

    public function handle(TikTokCustomerService $csService): int
    {
        $this->info('╔══════════════════════════════════════════════════════╗');
        $this->info('║  TikTok CS — Sync Conversations                    ║');
        $this->info('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        // ── Prune Logs ─────────────────────────────────────────────────
        if ($this->option('prune-logs')) {
            return $this->pruneLogs();
        }

        // ── Ambil akun aktif ───────────────────────────────────────────
        $accountId = $this->option('account');

        $query = AccountShopTiktok::query()
            ->where('status', 'active')
            ->whereNotNull('shop_cipher')
            ->whereNotNull('access_token');

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('⚠ Tidak ada akun TikTok aktif yang ditemukan.');
            return self::FAILURE;
        }

        $this->info("Ditemukan {$accounts->count()} akun aktif.");
        $this->newLine();

        $totalSynced = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $label = "[#{$account->id}] {$account->shop_name} ({$account->seller_name})";
            $this->info("▶ {$label}");

            if ($this->option('dry-run')) {
                $existingCount = TikTokConversation::where('account_id', $account->id)->count();
                $this->line("  ℹ  Percakapan tersimpan: {$existingCount}");
                $this->line("  ℹ  Dry run — tidak melakukan sync.");
                $this->newLine();
                continue;
            }

            // Check token expired
            if ($account->isTokenExpired()) {
                $this->warn("  ⚠ Token expired — skip akun ini. Refresh token terlebih dahulu.");
                $totalErrors++;
                continue;
            }

            try {
                $result = $csService->syncAllConversations($account);
                $totalSynced += $result['synced'];

                $this->info("  ✓ Synced: {$result['synced']} percakapan ({$result['pages']} halaman)");
            } catch (\Throwable $e) {
                $totalErrors++;
                $this->error("  ✗ Error: {$e->getMessage()}");
                Log::error('tiktok:sync-conversations failed', [
                    'account_id' => $account->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            $this->newLine();

            // Rate limiting antar akun
            usleep(500000); // 500ms
        }

        // ── Summary ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info("  Total synced : {$totalSynced} percakapan");
        if ($totalErrors > 0) {
            $this->warn("  Errors       : {$totalErrors}");
        }
        $this->info('═══════════════════════════════════════════════════════');

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Hapus webhook logs lama sesuai konfigurasi retensi.
     */
    private function pruneLogs(): int
    {
        $days = config('tiktok_cs.log_retention_days', 30);

        $this->info("Menghapus webhook logs lebih lama dari {$days} hari...");

        $deleted = TikTokWebhookLog::prune();

        $this->info("✓ {$deleted} log dihapus.");

        return self::SUCCESS;
    }
}
