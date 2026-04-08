<?php

namespace App\Http\Controllers;

use App\Jobs\UpdateTiktokInventoryJob;
use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use App\Services\PosStockService;
use App\Services\TiktokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        private PosStockService  $posStock,
        private TiktokApiService $tiktokService,
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
        $accounts = AccountShopTiktok::forUser()->get()->map(function ($account) {
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
                'status'            => $account->status,
                'id_outlet'         => $account->id_outlet,
                'token_expired'     => $account->isTokenExpired(),
                'last_update_stock' => $account->last_update_stock,
                'siap_sync'         => $siapSync,
                'tanpa_sku'         => $tanpaSku,
            ];
        });

        $jobsPending = 0;
        try {
            $jobsPending = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', 'tiktok-inventory')
                ->count();
        } catch (\Throwable $e) { /* tabel jobs mungkin belum ada */
        }

        $totalSiapSync = $accounts->sum('siap_sync');
        $totalTanpaSku = $accounts->sum('tanpa_sku');

        $userAccountIds = AccountShopTiktok::forUser()->pluck('id');
        $produkSiapSync = ProdukSaya::with('account')
            ->whereIn('account_id', $userAccountIds)
            ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
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
     * ================================================================ */
    public function runQueueWeb(Request $request): JsonResponse
    {
        @set_time_limit(120);

        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--queue'           => 'tiktok-inventory',
                '--stop-when-empty' => true,
                '--max-time'        => 25,
                '--tries'           => 3,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            $remaining = 0;
            try {
                $remaining = \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('queue', 'tiktok-inventory')
                    ->count();
            } catch (\Throwable $e) {
            }

            return response()->json([
                'status'         => 'selesai',
                'exit_code'      => $exitCode,
                'output'         => trim($output) ?: '(tidak ada output — queue mungkin sudah kosong)',
                'jobs_remaining' => $remaining,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => $e->getMessage(),
                'file'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
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
            $accounts = AccountShopTiktok::forUser()->whereNotNull('id_outlet')->get();
            $queued   = 0;
            $skipped  = [];
            $detail   = [];

            foreach ($accounts as $account) {
                // Ambil semua produk TIKTOK + TOKOPEDIA ACTIVATE
                // distinct() mencegah duplikat job karena 1 sku_id bisa ada di 2 baris (TIKTOK + TOKOPEDIA)
                $products = ProdukSaya::where('account_id', $account->id)
                    ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                    ->where('product_status', 'ACTIVATE')
                    ->select('product_id', 'sku_id', 'seller_sku')
                    ->distinct()
                    ->get();

                if ($products->isEmpty()) {
                    $skipped[] = $account->seller_name . ' (tidak ada produk TIKTOK/TOKOPEDIA ACTIVATE)';
                    continue;
                }

                $accountQueued = 0;
                foreach ($products as $product) {
                    UpdateTiktokInventoryJob::dispatch(
                        accountId: $account->id,
                        productId: $product->product_id,
                        skuId: $product->sku_id,
                        sellerSku: $product->seller_sku ?? '',
                        idOutlet: $account->id_outlet,
                    )->onQueue('tiktok-inventory');

                    $queued++;
                    $accountQueued++;
                }

                $detail[] = [
                    'account' => $account->seller_name,
                    'queued'  => $accountQueued,
                ];
            }

            return response()->json([
                'status'  => 'Jobs dispatched',
                'queued'  => $queued,
                'skipped' => $skipped,
                'detail'  => $detail,
                'info'    => 'Jobs masuk ke antrian. Queue worker akan push ke TikTok API secara bertahap.',
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
            // Semua TIKTOK + TOKOPEDIA ACTIVATE di-dispatch
            // distinct() mencegah duplikat job karena 1 sku_id bisa ada di 2 baris (TIKTOK + TOKOPEDIA)
            $products = ProdukSaya::where('account_id', $account->id)
                ->whereIn('platform', ['TIKTOK', 'TOKOPEDIA'])
                ->where('product_status', 'ACTIVATE')
                ->select('product_id', 'sku_id', 'seller_sku')
                ->distinct()
                ->get();

            $queued = 0;
            foreach ($products as $product) {
                UpdateTiktokInventoryJob::dispatch(
                    accountId: $account->id,
                    productId: $product->product_id,
                    skuId: $product->sku_id,
                    sellerSku: $product->seller_sku ?? '',
                    idOutlet: $account->id_outlet,
                )->onQueue('tiktok-inventory');
                $queued++;
            }

            return response()->json([
                'status'  => 'Jobs dispatched',
                'account' => $account->seller_name,
                'queued'  => $queued,
                'info'    => 'Jobs masuk ke antrian. Queue worker akan push ke TikTok API secara bertahap.',
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
     *  GET /stock/cron-sync-all?secret=xxx
     * ================================================================ */
    public function cronSyncAll(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.stock_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        // Guard: jika masih ada jobs pending di queue, skip dispatch
        // Mencegah penumpukan jobs jika queue worker belum selesai memproses batch sebelumnya
        try {
            $pending = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', 'tiktok-inventory')
                ->count();

            if ($pending > 0) {
                return response()->json([
                    'status'       => 'skipped',
                    'reason'       => 'Masih ada jobs pending di queue, dispatch dilewati.',
                    'jobs_pending' => $pending,
                    'tip'          => 'Tunggu queue worker selesai memproses semua jobs terlebih dahulu.',
                ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            // Tabel jobs belum ada — lanjut saja
        }

        return $this->syncAll();
    }

    /* ================================================================
     *  Cron via curl — Jalankan queue worker (tanpa exec/shell)
     *  GET /stock/run-queue?secret=xxx
     * ================================================================ */
    public function runQueue(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.stock_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        @set_time_limit(300);

        try {
            // Artisan::call() bekerja tanpa exec() — murni PHP internal
            $exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--queue'           => 'tiktok-inventory',
                '--stop-when-empty' => true,
                '--max-time'        => 55,
                '--tries'           => 3,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'status'    => 'Queue worker selesai',
                'exit_code' => $exitCode,
                'output'    => $output ?: '(tidak ada output — kemungkinan queue kosong)',
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'ERROR',
                'pesan'  => $e->getMessage(),
                'file'   => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
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
