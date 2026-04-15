<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use App\Models\StockSyncLog;
use App\Services\PosStockService;
use App\Services\ShopeeApiService;
use App\Services\TiktokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockSyncLogController extends Controller
{
    public function __construct(
        private PosStockService   $posStock,
        private ShopeeApiService  $shopeeService,
        private TiktokApiService  $tiktokService,
    ) {}

    /* ================================================================
     *  INDEX — Tampilkan log sync stok
     *  GET /stock/logs
     * ================================================================ */
    public function index(Request $request): \Illuminate\View\View
    {
        $query = StockSyncLog::query()->orderByDesc('synced_at');

        // ── Filters ──────────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('seller_sku', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('product_id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('synced_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('synced_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        // ── Stats ────────────────────────────────────────────────────
        $stats = [
            'total'   => StockSyncLog::count(),
            'success' => StockSyncLog::where('status', 'success')->count(),
            'failed'  => StockSyncLog::where('status', 'failed')->count(),
            'today'   => StockSyncLog::whereDate('synced_at', today())->count(),
            'today_failed' => StockSyncLog::where('status', 'failed')->whereDate('synced_at', today())->count(),
        ];

        // ── Account list for filter ──────────────────────────────────
        $accounts = StockSyncLog::select('account_id', 'account_name', 'platform')
            ->distinct()
            ->orderBy('platform')
            ->orderBy('account_name')
            ->get();

        return view('stock.sync-logs', compact('logs', 'stats', 'accounts'));
    }

    /* ================================================================
     *  PUSH ONE — Retry update stok 1 produk ke marketplace
     *  POST /stock/logs/{log}/push-one
     * ================================================================ */
    public function pushOne(StockSyncLog $log): JsonResponse
    {
        try {
            // Ambil produk dari produk_saya
            $product = ProdukSaya::where('product_id', $log->product_id)
                ->where('sku_id', $log->sku_id)
                ->where('account_id', $log->account_id)
                ->first();

            if (!$product) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Produk tidak ditemukan di database.',
                ], 404);
            }

            // Ambil stok terbaru dari POS
            $account  = $this->getAccount($log->platform, $log->account_id);
            if (!$account || !$account->id_outlet) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akun tidak ditemukan atau id_outlet belum di-set.',
                ], 404);
            }

            $posQty = 0;
            if (!empty($product->seller_sku)) {
                $posQty = $this->posStock->getStock($product->seller_sku, $account->id_outlet);
            }

            $oldQty = (int) $product->quantity;

            // Push ke API sesuai platform
            if ($log->platform === 'SHOPEE') {
                $result = $this->pushToShopee($account, $product, $posQty);
            } else {
                $result = $this->pushToTiktok($account, $product, $posQty);
            }

            // Update produk_saya.quantity
            $product->update(['quantity' => max(0, $posQty)]);

            // Catat log baru
            $newLog = StockSyncLog::create([
                'account_id'   => $log->account_id,
                'platform'     => $log->platform,
                'account_name' => $log->account_name,
                'product_id'   => $log->product_id,
                'sku_id'       => $log->sku_id,
                'seller_sku'   => $log->seller_sku,
                'title'        => $log->title,
                'old_quantity' => $oldQty,
                'pos_stock'    => $posQty,
                'pushed_stock' => $posQty,
                'status'       => 'success',
                'api_response' => $result,
                'retry_count'  => $log->retry_count + 1,
                'last_retry_at' => now(),
                'synced_at'    => now(),
            ]);

            // Update log lama — tandai sudah di-retry
            $log->update([
                'retry_count'  => $log->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            return response()->json([
                'status'    => 'success',
                'message'   => "Stok berhasil di-push: {$product->seller_sku} → {$posQty} unit",
                'log_id'    => $newLog->id,
                'old_qty'   => $oldQty,
                'new_qty'   => $posQty,
            ]);
        } catch (\Throwable $e) {
            // Catat log gagal
            StockSyncLog::create([
                'account_id'    => $log->account_id,
                'platform'      => $log->platform,
                'account_name'  => $log->account_name,
                'product_id'    => $log->product_id,
                'sku_id'        => $log->sku_id,
                'seller_sku'    => $log->seller_sku,
                'title'         => $log->title,
                'old_quantity'  => (int) ($product->quantity ?? $log->old_quantity),
                'pos_stock'     => $posQty ?? 0,
                'pushed_stock'  => 0,
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count'   => $log->retry_count + 1,
                'last_retry_at' => now(),
                'synced_at'     => now(),
            ]);

            $log->update([
                'retry_count'  => $log->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal push: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ================================================================
     *  PUSH BULK — Retry semua log yang gagal (filtered)
     *  POST /stock/logs/push-bulk
     * ================================================================ */
    public function pushBulk(Request $request): JsonResponse
    {
        @set_time_limit(300);

        // Bisa kirim specific IDs atau retry semua failed
        $logIds = $request->input('log_ids', []);

        if (!empty($logIds)) {
            $failedLogs = StockSyncLog::whereIn('id', $logIds)
                ->where('status', 'failed')
                ->get();
        } else {
            // Default: retry semua failed hari ini (max 200)
            $failedLogs = StockSyncLog::where('status', 'failed')
                ->whereDate('synced_at', today())
                ->orderByDesc('synced_at')
                ->limit(200)
                ->get();
        }

        if ($failedLogs->isEmpty()) {
            return response()->json([
                'status'  => 'info',
                'message' => 'Tidak ada log gagal yang perlu di-retry.',
            ]);
        }

        $success = 0;
        $failed  = 0;
        $errors  = [];

        foreach ($failedLogs as $log) {
            try {
                $product = ProdukSaya::where('product_id', $log->product_id)
                    ->where('sku_id', $log->sku_id)
                    ->where('account_id', $log->account_id)
                    ->first();

                if (!$product) {
                    $failed++;
                    $errors[] = "{$log->seller_sku}: produk tidak ditemukan";
                    continue;
                }

                $account = $this->getAccount($log->platform, $log->account_id);
                if (!$account || !$account->id_outlet) {
                    $failed++;
                    $errors[] = "{$log->seller_sku}: akun/outlet tidak ditemukan";
                    continue;
                }

                $posQty = 0;
                if (!empty($product->seller_sku)) {
                    $posQty = $this->posStock->getStock($product->seller_sku, $account->id_outlet);
                }

                $oldQty = (int) $product->quantity;

                // Push ke API
                if ($log->platform === 'SHOPEE') {
                    $result = $this->pushToShopee($account, $product, $posQty);
                } else {
                    $result = $this->pushToTiktok($account, $product, $posQty);
                }

                // Update produk_saya
                $product->update(['quantity' => max(0, $posQty)]);

                // Log sukses
                StockSyncLog::create([
                    'account_id'   => $log->account_id,
                    'platform'     => $log->platform,
                    'account_name' => $log->account_name,
                    'product_id'   => $log->product_id,
                    'sku_id'       => $log->sku_id,
                    'seller_sku'   => $log->seller_sku,
                    'title'        => $log->title,
                    'old_quantity' => $oldQty,
                    'pos_stock'    => $posQty,
                    'pushed_stock' => $posQty,
                    'status'       => 'success',
                    'api_response' => $result,
                    'retry_count'  => $log->retry_count + 1,
                    'last_retry_at' => now(),
                    'synced_at'    => now(),
                ]);

                $log->update([
                    'retry_count'  => $log->retry_count + 1,
                    'last_retry_at' => now(),
                ]);

                $success++;
                usleep(200_000); // 200ms jeda antar push
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "{$log->seller_sku}: {$e->getMessage()}";

                StockSyncLog::create([
                    'account_id'    => $log->account_id,
                    'platform'      => $log->platform,
                    'account_name'  => $log->account_name,
                    'product_id'    => $log->product_id,
                    'sku_id'        => $log->sku_id,
                    'seller_sku'    => $log->seller_sku,
                    'title'         => $log->title,
                    'old_quantity'  => (int) ($product->quantity ?? $log->old_quantity),
                    'pos_stock'     => $posQty ?? 0,
                    'pushed_stock'  => 0,
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count'   => $log->retry_count + 1,
                    'last_retry_at' => now(),
                    'synced_at'     => now(),
                ]);

                $log->update([
                    'retry_count'  => $log->retry_count + 1,
                    'last_retry_at' => now(),
                ]);
            }
        }

        return response()->json([
            'status'  => 'completed',
            'message' => "Retry selesai: {$success} berhasil, {$failed} gagal dari {$failedLogs->count()} total.",
            'success' => $success,
            'failed'  => $failed,
            'total'   => $failedLogs->count(),
            'errors'  => array_slice($errors, 0, 20), // max 20 error messages
        ]);
    }

    /* ================================================================
     *  CLEAR LOGS — Hapus log lama (>30 hari)
     *  POST /stock/logs/clear-old
     * ================================================================ */
    public function clearOld(): JsonResponse
    {
        $deleted = StockSyncLog::where('synced_at', '<', now()->subDays(30))->delete();

        return response()->json([
            'status'  => 'success',
            'message' => "{$deleted} log lama (>30 hari) berhasil dihapus.",
            'deleted' => $deleted,
        ]);
    }

    /* ================================================================
     *  Private: Push 1 produk ke Shopee API
     * ================================================================ */
    private function pushToShopee($account, ProdukSaya $product, int $qty): array
    {
        // Auto-refresh token jika expired
        if ($account->isTokenExpired()) {
            $tokenData = $this->shopeeService->refreshAccessToken(
                $account->refresh_token,
                (int) $account->shop_id
            );
            if (!empty($tokenData['access_token'])) {
                $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);
                $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000);
                $account->update([
                    'access_token'            => $tokenData['access_token'],
                    'access_token_expire_in'  => now()->addSeconds($expireIn),
                    'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'refresh_token_expire_in' => now()->addSeconds($refreshExpireIn),
                    'token_obtained_at'       => now(),
                    'status'                  => 'active',
                ]);
                $account->refresh();
            }
        }

        $isVariant = (string) $product->sku_id !== (string) $product->product_id;
        $modelId   = $isVariant ? (int) $product->sku_id : 0;

        $stockList = [
            [
                'model_id'     => $modelId,
                'seller_stock' => [
                    ['stock' => max(0, $qty)],
                ],
            ],
        ];

        return $this->shopeeService->updateStock(
            accessToken: $account->access_token,
            shopId: (int) $account->shop_id,
            itemId: (int) $product->product_id,
            stockList: $stockList,
        );
    }

    /* ================================================================
     *  Private: Push 1 produk ke TikTok API
     * ================================================================ */
    private function pushToTiktok($account, ProdukSaya $product, int $qty): array
    {
        // Auto-refresh token jika expired
        if ($account->isTokenExpired()) {
            $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);
            if (!empty($tokenData['access_token'])) {
                $account->update([
                    'access_token'            => $tokenData['access_token'],
                    'access_token_expire_in'  => \Carbon\Carbon::createFromTimestamp($tokenData['access_token_expire_in'] ?? 0),
                    'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'refresh_token_expire_in' => isset($tokenData['refresh_token_expire_in'])
                        ? \Carbon\Carbon::createFromTimestamp($tokenData['refresh_token_expire_in'])
                        : $account->refresh_token_expire_in,
                    'token_obtained_at'       => now(),
                ]);
                $account->refresh();
            }
        }

        return $this->tiktokService->updateInventory(
            accessToken: $account->access_token,
            shopCipher: $account->shop_cipher,
            productId: $product->product_id,
            skuId: $product->sku_id,
            quantity: $qty,
        );
    }

    /* ================================================================
     *  Private: Ambil account model berdasarkan platform
     * ================================================================ */
    private function getAccount(string $platform, int $accountId)
    {
        if ($platform === 'SHOPEE') {
            return AccountShopShopee::find($accountId);
        }
        return AccountShopTiktok::find($accountId);
    }
}
