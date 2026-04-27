<?php

namespace App\Http\Controllers;

use App\Jobs\SyncAccountInventoryJob;
use App\Jobs\SyncShopeeInventoryJob;
use App\Models\AccountShopShopee;
use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use App\Services\PosStockService;
use App\Services\ProductSyncService;
use App\Services\ShopeeProductSyncService;
use App\Services\TiktokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(
        private PosStockService   $posStock,
        private TiktokApiService  $tiktokService,
        private ProductSyncService $productSync,
        private ShopeeProductSyncService $shopeeProductSync,
    ) {}

    /* ================================================================
     *  TESTING — Lihat semua akun & kondisi
     *  GET /stock/test
     * ================================================================ */
    public function testStock(): JsonResponse
    {
        $accounts = AccountShopTiktok::all()->map(function ($account) {
            $baseQuery = ProdukSaya::where('account_id', $account->id);

            $totalSemua     = (clone $baseQuery)->count();
            $totalTiktok    = (clone $baseQuery)->where('platform', 'TIKTOK')->count();
            $totalTokopedia = (clone $baseQuery)->where('platform', 'TOKOPEDIA')->count();

            // TIKTOK + TOKOPEDIA ACTIVATE → akan di-sync (sku_id sebagai kunci push ke API)
            $siapSync = (clone $baseQuery)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE')
                ->count();

            // ACTIVATE tapi seller_sku kosong → stok tidak bisa diambil dari POS (qty=0)
            $aktiveTanpaSku = (clone $baseQuery)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE')
                ->where(function ($q) {
                    $q->whereNull('seller_sku')->orWhere('seller_sku', '');
                })
                ->count();

            $nonAktif = (clone $baseQuery)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', '!=', 'ACTIVATE')
                ->count();

            // Distribusi product_status (TIKTOK + TOKOPEDIA)
            $statusDistribusi = (clone $baseQuery)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->selectRaw('platform, product_status, COUNT(*) as jumlah')
                ->groupBy('platform', 'product_status')
                ->get()
                ->mapToGroups(fn($r) => [$r->platform => $r->product_status . ': ' . $r->jumlah])
                ->toArray();

            return [
                'id'                => $account->id,
                'seller_name'       => $account->seller_name,
                'shop_cipher'       => $account->shop_cipher ? 'Ada' : 'Kosong',
                'id_outlet'         => $account->id_outlet ?? 'Belum di-set',
                'token_expired'     => $account->isTokenExpired() ? 'EXPIRED' : 'Valid',
                'last_update_stock' => $account->last_update_stock?->format('Y-m-d H:i:s') ?? 'Belum pernah sync',
                'produk_stats'      => [
                    'total_semua'            => $totalSemua,
                    'total_tiktok'           => $totalTiktok,
                    'total_tokopedia'        => $totalTokopedia,
                    'siap_sync'              => $siapSync,         // TIKTOK+TOKOPEDIA ACTIVATE
                    'aktif_tanpa_seller_sku' => $aktiveTanpaSku,   // ACTIVATE tapi seller_sku kosong → qty=0
                    'status_non_aktif'       => $nonAktif,         // DELETED/FREEZE dll → dilewati
                ],
                'debug_product_status' => $statusDistribusi,  // ← lihat nilai product_status yg tersimpan
                'links' => [
                    'test_pos_stock' => url("/stock/test/{$account->id}/pos-stock"),
                    'test_push_one'  => url("/stock/test/{$account->id}/push-one"),
                    'sync_account'   => url("/stock/{$account->id}/sync"),
                ],
            ];
        });

        return response()->json([
            'status'   => 'OK',
            'accounts' => $accounts,
            'tips'     => [
                '1' => 'Cek id_outlet sudah di-set belum',
                '2' => 'Cek token_expired - jika EXPIRED sync tidak akan jalan',
                '3' => 'Akses test_pos_stock untuk cek koneksi DB POS',
                '4' => 'Akses test_push_one untuk test push 1 produk ke TikTok',
            ],
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /* ================================================================
     *  TESTING — Cek stok dari DB POS untuk akun tertentu
     *  GET /stock/test/{account}/pos-stock
     * ================================================================ */
    public function testPosStock(AccountShopTiktok $account)
    {
        if (!$account->id_outlet) {
            return back()->with('error', 'id_outlet belum di-set untuk akun ini! Atur dulu di halaman Integrasi.');
        }

        $semuaProduk = ProdukSaya::where('account_id', $account->id)
            ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
            ->orderBy('platform')
            ->orderBy('product_status')
            ->orderBy('seller_sku')
            ->get();

        $hasilSiap      = collect();
        $hasilSkuKosong = collect();
        $hasilDilewati  = collect();

        foreach ($semuaProduk as $product) {
            $platformLabel = $product->platform === 'TIKTOK' ? 'TikTok' : 'Tokopedia';

            if ($product->product_status !== 'ACTIVATE') {
                $hasilDilewati->push([
                    'platform'       => $platformLabel,
                    'title'          => $product->title,
                    'seller_sku'     => $product->seller_sku ?: '—',
                    'product_status' => $product->product_status,
                ]);
                continue;
            }

            if (empty($product->seller_sku)) {
                $hasilSkuKosong->push([
                    'platform'   => $platformLabel,
                    'title'      => $product->title,
                    'product_id' => $product->product_id,
                    'sku_id'     => $product->sku_id,
                ]);
                continue;
            }

            $stokPos = $this->posStock->getStock($product->seller_sku, $account->id_outlet);
            $stokMkt = (int) $product->quantity;
            $selisih = $stokPos - $stokMkt;

            $hasilSiap->push([
                'platform'     => $platformLabel,
                'title'        => $product->title,
                'seller_sku'   => $product->seller_sku,
                'sku_id'       => $product->sku_id,
                'product_id'   => $product->product_id,
                'stok_pos'     => $stokPos,
                'stok_mkt'     => $stokMkt,
                'selisih'      => $selisih,
                'perlu_update' => $stokPos !== $stokMkt,
            ]);
        }

        $summary = [
            'total_produk'    => $semuaProduk->count(),
            'total_tiktok'    => $semuaProduk->where('platform', 'TIKTOK')->count(),
            'total_tokopedia' => $semuaProduk->where('platform', 'TOKOPEDIA')->count(),
            'siap_sync'       => $hasilSiap->count(),
            'perlu_update'    => $hasilSiap->where('perlu_update', true)->count(),
            'sku_kosong'      => $hasilSkuKosong->count(),
            'dilewati'        => $hasilDilewati->count(),
        ];

        return view('stock.pos-stock', compact('account', 'summary', 'hasilSiap', 'hasilSkuKosong', 'hasilDilewati'));
    }

    /* ================================================================
     *  TESTING — Push 1 produk pertama ke TikTok (LANGSUNG, tanpa queue)
     *  GET /stock/test/{account}/push-one
     * ================================================================ */
    public function testPushOne(AccountShopTiktok $account): JsonResponse
    {
        if (!$account->id_outlet) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => 'id_outlet belum di-set!',
            ], 422);
        }

        // Ambil produk ACTIVATE pertama (TIKTOK atau TOKOPEDIA) — seller_sku tidak wajib
        $product = ProdukSaya::where('account_id', $account->id)
            ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
            ->where('product_status', 'ACTIVATE')
            ->orderByRaw('seller_sku IS NULL OR seller_sku = "" ASC') // prioritaskan yang ada seller_sku
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 'WARNING',
                'pesan'  => 'Tidak ada produk TIKTOK/TOKOPEDIA dengan status ACTIVATE. Cek apakah produk sudah di-sync dan statusnya ACTIVATE di Seller Center.',
            ], 404);
        }

        // Ambil stok dari POS pakai seller_sku (= nomor_product di POS)
        // Jika seller_sku kosong → qty = 0 (tidak ada mapping ke POS)
        $posNote  = 'seller_sku kosong — stok tidak bisa diambil dari POS, qty=0';
        $quantity = 0;
        if (!empty($product->seller_sku)) {
            $quantity = $this->posStock->getStock($product->seller_sku, $account->id_outlet);
            $posNote  = 'Stok diambil dari POS berdasarkan seller_sku';
        }

        $tokenRefreshed = false;
        if ($account->isTokenExpired()) {
            try {
                $newToken = $this->tiktokService->refreshAccessToken($account->refresh_token);
                $account->update([
                    'access_token'           => $newToken['access_token'],
                    'access_token_expire_in' => now()->addSeconds($newToken['access_token_expire_in'] ?? 0),
                ]);
                $account->refresh();
                $tokenRefreshed = true;
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'ERROR',
                    'pesan'  => 'Token expired & gagal refresh: ' . $e->getMessage(),
                ], 500);
            }
        }

        try {
            $result = $this->tiktokService->updateInventory(
                accessToken: $account->access_token,
                shopCipher: $account->shop_cipher,
                productId: $product->product_id,
                skuId: $product->sku_id,
                quantity: $quantity,
            );

            return response()->json([
                'status'          => 'BERHASIL',
                'account'         => $account->seller_name,
                'produk'          => $product->title,
                'seller_sku'      => $product->seller_sku ?: '(kosong)',
                'sku_id'          => $product->sku_id,
                'product_id'      => $product->product_id,
                'stok_dikirim'    => $quantity,
                'pos_note'        => $posNote,
                'token_refreshed' => $tokenRefreshed,
                'tiktok_response' => $result,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'status'       => 'GAGAL',
                'pesan'        => $e->getMessage(),
                'seller_sku'   => $product->seller_sku,
                'stok_dikirim' => $quantity,
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /* ================================================================
     *  Stock Sync Dashboard — tampilan manajemen stok
     *  GET /stock
     * ================================================================ */
    public function dashboard(): \Illuminate\View\View
    {
        // ── TikTok accounts ──────────────────────────────────────────
        $tiktokAccounts = AccountShopTiktok::forUser()->get()->map(function ($account) {
            $base = ProdukSaya::where('account_id', $account->id)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE');

            $siapSync = (clone $base)
                ->whereNotNull('seller_sku')
                ->where('seller_sku', '!=', '')
                ->count();

            $tanpaSku = (clone $base)
                ->where(function ($q) {
                    $q->whereNull('seller_sku')->orWhere('seller_sku', '');
                })
                ->count();

            return (object) [
                'id'                => $account->id,
                'seller_name'       => $account->seller_name,
                'platform'          => 'TIKTOK',
                'status'            => $account->status,
                'id_outlet'         => $account->id_outlet,
                'token_expired'     => $account->isTokenExpired(),
                'last_update_stock' => $account->last_update_stock,
                'siap_sync'         => $siapSync,
                'tanpa_sku'         => $tanpaSku,
                // summary of last pushed activity
                'last_pushed_at'    => ProdukSaya::where('account_id', $account->id)->max('last_pushed_at'),
                'last_pushed_count' => ProdukSaya::where('account_id', $account->id)->whereNotNull('last_pushed_at')->count(),
                'products_total'    => ProdukSaya::where('account_id', $account->id)->count(),
            ];
        });

        // ── Shopee accounts ──────────────────────────────────────────
        $shopeeAccounts = AccountShopShopee::forUser()->where('status', 'active')->get()->map(function ($account) {
            $base = ProdukSaya::where('account_id', $account->id)
                ->where('platform', 'SHOPEE')
                ->where('product_status', 'ACTIVATE');

            $siapSync = (clone $base)
                ->whereNotNull('seller_sku')
                ->where('seller_sku', '!=', '')
                ->count();

            $tanpaSku = (clone $base)
                ->where(function ($q) {
                    $q->whereNull('seller_sku')->orWhere('seller_sku', '');
                })
                ->count();

            return (object) [
                'id'                => $account->id,
                'seller_name'       => $account->seller_name,
                'platform'          => 'SHOPEE',
                'status'            => $account->status,
                'id_outlet'         => $account->id_outlet,
                'token_expired'     => $account->isTokenExpired(),
                'last_update_stock' => $account->last_update_stock,
                'siap_sync'         => $siapSync,
                'tanpa_sku'         => $tanpaSku,
                // summary of last pushed activity
                'last_pushed_at'    => ProdukSaya::where('account_id', $account->id)->max('last_pushed_at'),
                'last_pushed_count' => ProdukSaya::where('account_id', $account->id)->whereNotNull('last_pushed_at')->count(),
                'products_total'    => ProdukSaya::where('account_id', $account->id)->count(),
            ];
        });

        $accounts = $tiktokAccounts->merge($shopeeAccounts);

        $jobsPending = 0;
        try {
            $jobsPending = \Illuminate\Support\Facades\DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->count();
        } catch (\Throwable $e) { /* tabel jobs mungkin belum ada */
        }

        $totalSiapSync = $accounts->sum('siap_sync');
        $totalTanpaSku = $accounts->sum('tanpa_sku');

        // ── Product list for dashboard (all platforms) ───────────────
        $tiktokAccountIds = AccountShopTiktok::forUser()->pluck('id');
        $shopeeAccountIds = AccountShopShopee::forUser()->pluck('id');

        $produkSiapSync = ProdukSaya::query()
            ->where(function ($q) use ($tiktokAccountIds, $shopeeAccountIds) {
                $q->where(function ($sub) use ($tiktokAccountIds) {
                    $sub->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                        ->whereIn('account_id', $tiktokAccountIds);
                })->orWhere(function ($sub) use ($shopeeAccountIds) {
                    $sub->where('platform', 'SHOPEE')
                        ->whereIn('account_id', $shopeeAccountIds);
                });
            })
            ->where('product_status', 'ACTIVATE')
            ->whereNotNull('seller_sku')
            ->where('seller_sku', '!=', '')
            ->orderBy('account_id')
            ->orderBy('platform')
            ->orderBy('title')
            ->paginate(30)
            ->withQueryString();

        return view('stock.dashboard', compact(
            'accounts',
            'jobsPending',
            'produkSiapSync',
            'totalSiapSync',
            'totalTanpaSku'
        ));
    }

    /* ================================================================
     *  Jalankan queue worker dari web UI (CSRF protected, tanpa secret)
     *  POST /stock/run-queue-web
     *
     *  ⚠ TIDAK menggunakan Artisan::call() — terlalu lambat, mati di HTTP timeout.
     *  Fungsi ini HANYA melepas "stuck jobs" (reserved > 5 menit) agar cron
     *  worker bisa memprosesnya ulang. Pemrosesan actual tetap oleh cron.
     * ================================================================ */
    public function runQueueWeb(Request $request): JsonResponse
    {
        // Lepas stuck jobs from both queues
        $released = 0;
        try {
            $released = DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', now()->subMinutes(5)->timestamp)
                ->update(['reserved_at' => null]);
        } catch (\Throwable $e) {
        }

        $available = $delayed = $reserved = 0;
        try {
            $now       = now()->timestamp;
            $available = DB::table('jobs')->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->whereNull('reserved_at')->where('available_at', '<=', $now)->count();
            $delayed   = DB::table('jobs')->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->whereNull('reserved_at')->where('available_at', '>', $now)->count();
            $reserved  = DB::table('jobs')->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->whereNotNull('reserved_at')->count();
        } catch (\Throwable $e) {
        }

        $total = $available + $delayed + $reserved;

        $info = $released > 0
            ? "{$released} job stuck berhasil dilepas. Cron worker akan memprosesnya dalam ~1 menit."
            : ($total > 0
                ? "Antrian: {$available} siap · {$reserved} sedang jalan · {$delayed} menunggu retry."
                : 'Antrian kosong. Klik "Sync Semua Akun" untuk mulai sync stok.');

        return response()->json([
            'status'         => 'selesai',
            'released'       => $released,
            'available'      => $available,
            'delayed'        => $delayed,
            'running'        => $reserved,
            'jobs_remaining' => $total,
            'info'           => $info,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /* ================================================================
     *  Sync stok SEMUA akun — dispatch ke queue
     *  GET /stock/sync-all
     * ================================================================ */
    public function syncAll(): JsonResponse
    {
        // Naikkan batas waktu eksekusi — shared hosting biasanya 30 detik
        @set_time_limit(300);

        try {
            $queued   = 0;
            $skipped  = [];
            $detail   = [];

            // ── TikTok accounts ──────────────────────────────────────
            $tiktokAccounts = AccountShopTiktok::forUser()->whereNotNull('id_outlet')->get();
            foreach ($tiktokAccounts as $account) {
                SyncAccountInventoryJob::dispatch(
                    accountId: $account->id,
                )->onQueue('tiktok-inventory');

                $queued++;
                $detail[] = [
                    'platform' => 'TIKTOK',
                    'account'  => $account->seller_name,
                    'queued'   => 1,
                ];
            }

            // ── Shopee accounts ──────────────────────────────────────
            $shopeeAccounts = AccountShopShopee::forUser()->where('status', 'active')->whereNotNull('id_outlet')->get();

            foreach ($shopeeAccounts as $account) {
                SyncShopeeInventoryJob::dispatch(
                    accountId: $account->id,
                )->onQueue('shopee-inventory');

                $queued++;
                $detail[] = [
                    'platform' => 'SHOPEE',
                    'account'  => $account->seller_name,
                    'queued'   => 1,
                ];
            }

            return response()->json([
                'status'  => 'Jobs dispatched',
                'queued'  => $queued,
                'skipped' => $skipped,
                'detail'  => $detail,
                'info'    => 'Batch jobs masuk ke antrian. TikTok + Shopee.',
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => $e->getMessage(),
                'file'   => basename($e->getFile()) . ':' . $e->getLine(),
                'tip'    => 'Kemungkinan tabel jobs belum dibuat. Buka /migrate.php terlebih dahulu.',
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /* ================================================================
     *  Sync stok 1 akun — dispatch ke queue
     *  GET /stock/{account}/sync
     * ================================================================ */
    public function syncAccount(AccountShopTiktok $account): JsonResponse
    {
        if (!$account->id_outlet) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => 'id_outlet belum di-set untuk akun ini!',
            ], 422);
        }

        @set_time_limit(300);

        try {
            // ✅ BATCH JOB: dispatch 1 job untuk seluruh akun ini
            // SyncAccountInventoryJob menangani semua SKU dalam 1 job (getStockBulk)
            SyncAccountInventoryJob::dispatch(
                accountId: $account->id,
            )->onQueue('tiktok-inventory');

            return response()->json([
                'status'  => 'Jobs dispatched',
                'account' => $account->seller_name,
                'queued'  => 1,
                'info'    => '1 batch job masuk ke antrian. Job memproses semua SKU akun ini sekaligus (getStockBulk + push per SKU).',
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => $e->getMessage(),
                'file'   => basename($e->getFile()) . ':' . $e->getLine(),
                'tip'    => 'Kemungkinan tabel jobs belum dibuat. Buka /migrate.php terlebih dahulu.',
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    /* ================================================================
     *  Cron via curl — Trigger sync-all dengan secret key
     *  GET /stock/cron-sync-all?secret=xxx               ← stock sync saja (setiap 5 menit)
     *  GET /stock/cron-sync-all?secret=xxx&sync_products=1  ← + product sync (1x/hari)
     *
     *  KENAPA DIPISAH?
     *   - Daftar produk (SKU ID, nama) jarang berubah → sync cukup 1x/hari
     *   - Stok berubah terus → sync setiap 5 menit, TANPA overhead product sync
     *   - Hasil: 10x lebih cepat per siklus stock-sync
     *
     *  ALUR default (stock only):
     *    STEP 1 — Guard        : skip jika ada batch jobs masih pending
     *    STEP 2 — Dispatch     : SyncAccountInventoryJob (1 job per akun)
     *
     *  ALUR dengan ?sync_products=1:
     *    STEP 1 — Sync produk  : TikTok API → update produk_saya
     *    STEP 2 — Guard        : skip jika ada batch jobs masih pending
     *    STEP 3 — Dispatch     : SyncAccountInventoryJob (1 job per akun)
     * ================================================================ */
    public function cronSyncAll(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.stock_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        @set_time_limit(300);

        $syncProducts      = filter_var($request->query('sync_products', false), FILTER_VALIDATE_BOOLEAN);
        $productSyncResult = [];

        // ── STEP 1 (opsional): Sync daftar produk dari API ───────────────
        if ($syncProducts) {
            // TikTok product sync
            $tiktokAccounts = AccountShopTiktok::where('status', 'active')
                ->whereNotNull('shop_cipher')
                ->whereNotNull('access_token')
                ->get();

            foreach ($tiktokAccounts as $account) {
                $r = $this->productSync->syncForAccount($account);
                $productSyncResult[] = [
                    'platform' => 'TIKTOK',
                    'account'  => $account->shop_name ?? $account->seller_name,
                    'saved'    => $r['saved'],
                    'pages'    => $r['pages'],
                    'error'    => $r['error'],
                ];
                usleep(300_000);
            }

            // Shopee product sync
            $shopeeAccounts = AccountShopShopee::where('status', 'active')
                ->whereNotNull('access_token')
                ->get();

            foreach ($shopeeAccounts as $account) {
                $r = $this->shopeeProductSync->syncForAccount($account);
                $productSyncResult[] = [
                    'platform' => 'SHOPEE',
                    'account'  => $account->seller_name,
                    'saved'    => $r['saved'],
                    'pages'    => $r['pages'],
                    'error'    => $r['error'],
                ];
                usleep(300_000);
            }
        }

        // ── STEP 2: Guard — skip HANYA jika ada jobs yang sedang aktif diproses ──
        // Menggunakan reserved_at (bukan pending count) agar tidak skip saat
        // worker idle tapi masih ada job sisa dari batch sebelumnya.
        try {
            $activeThreshold = now()->subMinutes(3)->timestamp;

            $activelyRunning = \Illuminate\Support\Facades\DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '>=', $activeThreshold)
                ->count();

            if ($activelyRunning > 0) {
                return response()->json([
                    'status'          => 'skipped_jobs_still_running',
                    'product_sync'    => $productSyncResult ?: null,
                    'reason'          => 'Masih ada batch jobs yang sedang aktif diproses oleh worker.',
                    'jobs_running'    => $activelyRunning,
                ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            // Bersihkan stuck jobs (reserved > 10 menit) agar tidak memblokir
            \Illuminate\Support\Facades\DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', now()->subMinutes(10)->timestamp)
                ->update(['reserved_at' => null]);
        } catch (\Throwable $e) {
        }

        // ── STEP 3: Dispatch batch jobs (TikTok + Shopee) ────────────────
        $stockResponse = $this->syncAll();
        $stockData     = json_decode($stockResponse->getContent(), true) ?? [];

        return response()->json(
            array_merge(['product_sync' => $productSyncResult ?: null], $stockData),
            200,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    /* ================================================================
     *  Cron via curl — Jalankan queue worker (tanpa exec/shell)
     *  GET /stock/run-queue?secret=xxx
     *
     *  DEPRECATED: Gunakan /queue-worker.php (standalone, lebih reliable).
     *  Endpoint ini tetap dipertahankan sebagai backup.
     *
     *  PENTING:
     *  - ignore_user_abort(true) → PHP tetap jalan meski curl disconnect
     *  - set_time_limit(600)     → 10 menit max
     *  - --max-jobs=1            → proses 1 job per panggilan
     * ================================================================ */
    public function runQueue(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.stock_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        // PHP tetap hidup meski curl disconnect (timeout biasanya 60 detik)
        ignore_user_abort(true);
        @set_time_limit(600);

        try {
            // Cek dulu apakah ada job
            $pending = DB::table('jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->whereNull('reserved_at')
                ->count();

            if ($pending === 0) {
                return response()->json([
                    'status'  => 'Queue kosong',
                    'pending' => 0,
                ]);
            }

            // Proses 1 job saja — aman untuk HTTP timeout
            $exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--queue'    => 'tiktok-inventory,shopee-inventory',
                '--max-jobs' => 1,
                '--tries'    => 2,
                '--timeout'  => 540,
                '--memory'   => 256,
            ]);

            $output    = \Illuminate\Support\Facades\Artisan::output();
            $remaining = DB::table('jobs')->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])->count();

            return response()->json([
                'status'         => 'Worker selesai',
                'exit_code'      => $exitCode,
                'jobs_remaining' => $remaining,
                'output'         => trim($output) ?: '(job selesai)',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => $e->getMessage(),
                'file'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    // syncProgress() removed — live monitor polling disabled to reduce load
    // If a cached summary endpoint is desired later, implement here using Cache::remember(...)

    /* ================================================================
     *  Hapus failed jobs & dispatch ulang semua akun
     *  POST /stock/clear-failed
     * ================================================================ */
    public function clearFailed(): JsonResponse
    {
        try {
            $deleted = DB::table('failed_jobs')
                ->whereIn('queue', ['tiktok-inventory', 'shopee-inventory'])
                ->delete();
        } catch (\Throwable $e) {
            $deleted = 0;
        }

        // Dispatch ulang
        $stockResponse = $this->syncAll();
        $stockData     = json_decode($stockResponse->getContent(), true) ?? [];

        return response()->json(array_merge($stockData, [
            'failed_cleared' => $deleted,
            'info' => "Hapus {$deleted} failed jobs + dispatch ulang semua akun.",
        ]), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /* ================================================================
     *  Set id_outlet untuk akun
     *  POST /stock/{account}/set-outlet
     * ================================================================ */
    public function setOutlet(Request $request, AccountShopTiktok $account): JsonResponse
    {
        $request->validate(['id_outlet' => 'required|integer']);

        $account->update(['id_outlet' => $request->id_outlet]);

        return response()->json([
            'status'    => 'id_outlet berhasil di-set',
            'account'   => $account->seller_name,
            'id_outlet' => $account->id_outlet,
        ]);
    }
}
