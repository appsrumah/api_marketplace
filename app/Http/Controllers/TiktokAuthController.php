<?php

namespace App\Http\Controllers;

use App\Http\Controllers\IntegrationController;
use App\Models\AccountShopTiktok;
use App\Models\ProductDetail;
use App\Models\ProdukSaya;
use App\Services\ProductSyncService;
use App\Services\TiktokApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiktokAuthController extends Controller
{
    public function __construct(
        private TiktokApiService  $tiktokService,
        private ProductSyncService $productSync,
    ) {}

    /* ---------- Redirect to TikTok OAuth ---------- */
    public function redirect()
    {
        return redirect()->away($this->tiktokService->getAuthUrl());
    }

    // =========================================================================
    // HELPER — Konversi expire_in dari TikTok ke Carbon datetime
    //
    // TikTok mengirim Unix Timestamp LANGSUNG (bukan durasi detik):
    //   access_token_expire_in  = 1776436019  → 15 Apr 2026 09:53 WIB
    //   refresh_token_expire_in = 4878264833  → ~90 hari ke depan (tahun 2124)
    //
    // JANGAN pakai now()->addSeconds() — hasilnya akan tahun 58.000!
    // =========================================================================
    private function parseExpireTimestamp(?int $expireIn): ?Carbon
    {
        if (!$expireIn) return null;
        return Carbon::createFromTimestamp($expireIn);
    }

    /* ---------- Callback — TikTok OAuth redirect handler ---------- */
    public function callback(Request $request)
    {
        // Jika user menolak izin di TikTok
        if ($request->has('error')) {
            return redirect()->route('dashboard')
                ->with('error', 'Otorisasi dibatalkan: ' . $request->query('error_description', $request->query('error')));
        }

        $authCode = $request->query('code') ?? $request->query('auth_code');

        if (!$authCode) {
            return redirect()->route('dashboard')
                ->with('error', 'Authorization code tidak ditemukan. Pastikan URL callback sudah benar.');
        }

        try {
            // ======== STEP 1: Exchange auth_code → access_token ========
            $tokenData = $this->tiktokService->getAccessToken($authCode);

            if (empty($tokenData['access_token'])) {
                throw new \RuntimeException('TikTok tidak mengembalikan access_token.');
            }

            $now        = now();
            $sellerName = $tokenData['seller_name'] ?? 'Unknown Seller';

            // ======== STEP 2: Get Auth Shop → ambil shop_id, shop_name, shop_cipher ========
            $shopId     = null;
            $shopName   = null;
            $shopCipher = null;

            try {
                $shops = $this->tiktokService->getAuthShop($tokenData['access_token']);
                if (!empty($shops)) {
                    $shop       = $shops[0];
                    $shopId     = $shop['id']     ?? null;
                    $shopName   = $shop['name']   ?? null;
                    $shopCipher = $shop['cipher'] ?? null;
                    Log::info("✅ Shop info: id={$shopId}, name={$shopName}, cipher={$shopCipher}");
                }
            } catch (\Throwable $e) {
                Log::warning('getAuthShop gagal (dilanjutkan tanpa cipher): ' . $e->getMessage());
            }

            // ======== STEP 3: Simpan / update akun di database ========
            $account = AccountShopTiktok::updateOrCreate(
                ['seller_name' => $sellerName],
                [
                    'seller_name'             => $sellerName,
                    'seller_base_region'      => $tokenData['seller_base_region'] ?? null,
                    'access_token'            => $tokenData['access_token'],
                    // ✅ TikTok kirim Unix Timestamp langsung, bukan durasi detik
                    'access_token_expire_in'  => $this->parseExpireTimestamp($tokenData['access_token_expire_in'] ?? null),
                    'refresh_token'           => $tokenData['refresh_token']            ?? '',
                    'refresh_token_expire_in' => $this->parseExpireTimestamp($tokenData['refresh_token_expire_in'] ?? null),
                    'shop_id'                 => $shopId,
                    'shop_name'               => $shopName,
                    'shop_cipher'             => $shopCipher,
                    'status'                  => 'active',
                    'token_obtained_at'       => $now,
                ]
            );

            Log::info("✅ Akun TikTok disimpan: id={$account->id}, seller={$account->seller_name}");

            // ======== STEP 3b: Assign user_id & channel_id dari session ========
            IntegrationController::assignFromSession($account);

            // ======== STEP 4: Auto sync semua produk ========
            if ($account->shop_cipher) {
                $syncResult = $this->productSync->syncForAccount($account);
                $saved      = $syncResult['saved'];

                Log::info("✅ Produk tersimpan: {$saved}, halaman: {$syncResult['pages']}, duplikat: {$syncResult['skipped']}");

                return redirect()->route('dashboard')->with(
                    'success',
                    "✅ Otorisasi berhasil! Akun \"{$account->seller_name}\" terhubung. " .
                        "{$saved} produk disimpan dari {$syncResult['pages']} halaman."
                );
            }

            return redirect()->route('dashboard')->with(
                'warning',
                "✅ Otorisasi berhasil! Akun \"{$account->seller_name}\" terhubung, " .
                    "tetapi shop cipher tidak ditemukan. Silakan klik Sync secara manual."
            );
        } catch (\Exception $e) {
            Log::error('TikTok callback error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('dashboard')
                ->with('error', 'Gagal menambahkan akun: ' . $e->getMessage());
        }
    }

    /* ---------- Manual sync products for an account ---------- */
    public function syncProducts(AccountShopTiktok $account)
    {
        try {
            // Sync daftar produk via ProductSyncService (token refresh + fetchAllProducts + upsert)
            $syncResult = $this->productSync->syncForAccount($account);

            if ($syncResult['error']) {
                return back()->with('error', 'Sync gagal: ' . $syncResult['error']);
            }

            // ── Auto-fetch product details dari TikTok API ──────────
            $detailsFetched = $this->fetchProductDetails($account);

            return back()->with(
                'success',
                "Sync berhasil! {$syncResult['saved']} produk diperbarui/ditambahkan dari {$syncResult['pages']} halaman. " .
                    "Duplikat dilewati: {$syncResult['skipped']}. " .
                    "Detail produk diambil: {$detailsFetched}."
            );
        } catch (\Exception $e) {
            Log::error('Sync error: ' . $e->getMessage());
            return back()->with('error', 'Sync gagal: ' . $e->getMessage());
        }
    }

    /* ---------- Delete account + products ---------- */
    public function destroy(AccountShopTiktok $account)
    {
        $name = $account->seller_name;
        $account->delete(); // cascade deletes products

        return redirect()->route('dashboard')
            ->with('success', "Akun \"{$name}\" berhasil dihapus.");
    }

    /* ---------- Auto-fetch product details dari TikTok API ---------- */
    private function fetchProductDetails(AccountShopTiktok $account): int
    {
        $fetched = 0;

        try {
            @set_time_limit(300);

            // Ambil produk TIKTOK ACTIVATE yang belum punya detail
            $products = ProdukSaya::where('account_id', $account->id)
                ->where('platform', 'TIKTOK')
                ->whereIn('product_status', ['ACTIVATE', 'SELLER_DEACTIVATED'])
                ->whereNotExists(function ($q) {
                    $q->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('product_details')
                        ->whereColumn('product_details.product_id', 'produk_saya.product_id')
                        ->whereColumn('product_details.account_id', 'produk_saya.account_id');
                })
                ->select('product_id')
                ->distinct()
                ->limit(50) // batasi agar tidak timeout
                ->get();

            if ($products->isEmpty()) {
                return 0;
            }

            $accessToken = $account->access_token;

            // Refresh token jika expired
            if ($account->isTokenExpired()) {
                $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);
                $account->update([
                    'access_token'            => $tokenData['access_token'],
                    // ✅ FIX: Unix Timestamp bukan durasi detik
                    'access_token_expire_in'  => $this->parseExpireTimestamp($tokenData['access_token_expire_in'] ?? null),
                    'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'refresh_token_expire_in' => isset($tokenData['refresh_token_expire_in'])
                        ? $this->parseExpireTimestamp($tokenData['refresh_token_expire_in'])
                        : $account->refresh_token_expire_in,
                    'token_obtained_at'       => now(),
                ]);
                $account->refresh();
                $accessToken = $account->access_token;
            }

            foreach ($products as $prod) {
                try {
                    $apiData = $this->tiktokService->getProductDetail(
                        $accessToken,
                        $account->shop_cipher,
                        $prod->product_id
                    );

                    $category  = $apiData['category_chains'] ?? [];
                    $lastCat   = end($category);
                    $packaging = $apiData['package_dimensions'] ?? [];

                    ProductDetail::updateOrCreate(
                        ['product_id' => $prod->product_id, 'account_id' => $account->id],
                        [
                            'platform'                     => 'TIKTOK',
                            'title'                        => $apiData['title'] ?? null,
                            'description'                  => $apiData['description'] ?? null,
                            'category_id'                  => $lastCat['id'] ?? null,
                            'category_name'                => $lastCat['local_name'] ?? $lastCat['parent_id'] ?? null,
                            'main_images'                  => $apiData['main_images'] ?? [],
                            'video'                        => $apiData['video'] ?? null,
                            'skus'                         => $apiData['skus'] ?? [],
                            'product_status'               => $apiData['status'] ?? null,
                            'product_attributes'           => $apiData['product_attributes'] ?? [],
                            'size_chart'                   => $apiData['size_chart'] ?? null,
                            'brand_id'                     => $apiData['brand']['id'] ?? null,
                            'brand_name'                   => $apiData['brand']['name'] ?? null,
                            'package_weight'               => $apiData['package_weight']['value'] ?? null,
                            'package_length'               => $packaging['length'] ?? null,
                            'package_width'                => $packaging['width'] ?? null,
                            'package_height'               => $packaging['height'] ?? null,
                            'package_dimensions_unit'      => $packaging['unit'] ?? null,
                            'product_certifications'       => $apiData['product_certifications'] ?? [],
                            'delivery_options'             => $apiData['delivery_options'] ?? [],
                            'integrated_platform_statuses' => $apiData['integrated_platform_statuses'] ?? [],
                            'tiktok_create_time'           => $apiData['create_time'] ?? null,
                            'tiktok_update_time'           => $apiData['update_time'] ?? null,
                            'raw_data'                     => $apiData,
                        ]
                    );

                    $fetched++;

                    // Rate-limit: 100ms antar request agar tidak di-throttle oleh TikTok
                    usleep(100_000);
                } catch (\Throwable $e) {
                    Log::warning("Gagal ambil detail produk {$prod->product_id}: " . $e->getMessage());
                    continue; // lanjut ke produk berikutnya
                }
            }
        } catch (\Throwable $e) {
            Log::error('fetchProductDetails batch error: ' . $e->getMessage());
        }

        return $fetched;
    }

    // =========================================================================
    // CRON — Refresh token H-1 sebelum expire
    // Route: GET /tiktok/cron-refresh-token?secret=xxx
    // =========================================================================
    public function cronRefreshToken(Request $request): JsonResponse
    {
        if ($request->query('secret') !== config('app.stock_sync_secret')) {
            return response()->json(['status' => 'Unauthorized'], 401);
        }

        @set_time_limit(300);

        // Cari akun aktif yang tokennya akan expire <= 24 jam ke depan (H-1)
        // Termasuk yang sudah expired agar bisa di-recover
        $accounts = AccountShopTiktok::where('status', 'active')
            ->whereNotNull('refresh_token')
            ->where(function ($q) {
                $q->whereNull('access_token_expire_in')
                    ->orWhere('access_token_expire_in', '<=', now()->addDay());
            })
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'status'  => 'OK',
                'message' => 'Tidak ada token yang perlu di-refresh.',
            ], 200);
        }

        $refreshed = 0;
        $failed    = 0;
        $results   = [];

        foreach ($accounts as $account) {
            $row = [
                'shop'       => $account->shop_name ?? $account->seller_name,
                'old_expire' => $account->access_token_expire_in?->format('Y-m-d H:i:s'),
                'status'     => null,
                'new_expire' => null,
                'error'      => null,
            ];

            try {
                $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);

                if (empty($tokenData['access_token'])) {
                    throw new \RuntimeException('Response refresh token kosong dari TikTok.');
                }

                $account->update([
                    'access_token'            => $tokenData['access_token'],
                    'access_token_expire_in'  => $this->parseExpireTimestamp($tokenData['access_token_expire_in'] ?? null),
                    'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                    'refresh_token_expire_in' => isset($tokenData['refresh_token_expire_in'])
                        ? $this->parseExpireTimestamp($tokenData['refresh_token_expire_in'])
                        : $account->refresh_token_expire_in,
                    'token_obtained_at'       => now(),
                ]);

                $account->refresh();
                $row['status']     = 'refreshed';
                $row['new_expire'] = $account->access_token_expire_in?->format('Y-m-d H:i:s');
                $refreshed++;

                Log::info('cronRefreshToken: token berhasil di-refresh', [
                    'shop'       => $account->shop_name,
                    'new_expire' => $row['new_expire'],
                ]);
            } catch (\Throwable $e) {
                $row['status'] = 'failed';
                $row['error']  = $e->getMessage();
                $failed++;

                Log::error('cronRefreshToken: gagal refresh token', [
                    'account_id' => $account->id,
                    'shop'       => $account->shop_name,
                    'error'      => $e->getMessage(),
                ]);
            }

            $results[] = $row;
            usleep(300_000); // 300ms jeda antar request
        }

        return response()->json([
            'status'    => 'OK',
            'checked'   => count($results),
            'refreshed' => $refreshed,
            'failed'    => $failed,
            'results'   => $results,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Terima token dari callback.php (server oleh2indonesia.com)
     * Route: POST /tiktok/internal-callback
     */
    public function internalCallback(Request $request): JsonResponse
    {
        // ── 1. Verifikasi secret ─────────────────────────────────
        $secret = $request->input('secret') ?? $request->header('X-Forward-Secret');

        if ($secret !== config('services.tiktok.forward_secret')) {
            Log::warning('internalCallback: secret tidak cocok', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ── 2. Validasi data ─────────────────────────────────────
        $validated = $request->validate([
            'access_token'            => 'required|string',
            'access_token_expire_in'  => 'required|integer',
            'refresh_token'           => 'required|string',
            'refresh_token_expire_in' => 'required|integer',
            'seller_name'             => 'nullable|string',
            'seller_base_region'      => 'nullable|string',
        ]);

        // ── 3. Ambil info shop dari TikTok API ───────────────────
        $shopInfo = $this->getAuthorizedShop($validated['access_token']);

        // ── 4. Simpan atau update ke database ────────────────────
        $account = AccountShopTiktok::updateOrCreate(
            [
                // Kalau sudah ada seller_name yang sama, update
                'seller_name' => $validated['seller_name'] ?? 'Unknown Seller',
            ],
            [
                'seller_name'             => $validated['seller_name']             ?? 'Unknown Seller',
                'seller_base_region'      => $validated['seller_base_region']      ?? null,
                'access_token'            => $validated['access_token'],
                'access_token_expire_in'  => $this->parseExpireTimestamp($validated['access_token_expire_in']),
                'refresh_token'           => $validated['refresh_token'],
                'refresh_token_expire_in' => $this->parseExpireTimestamp($validated['refresh_token_expire_in']),
                'shop_id'                 => $shopInfo['shop_id']     ?? null,
                'shop_name'               => $shopInfo['shop_name']   ?? null,
                'shop_cipher'             => $shopInfo['shop_cipher'] ?? null,
                'status'                  => 'active',
                'token_obtained_at'       => now(),
                'last_sync_at'            => now(),
            ]
        );

        Log::info('internalCallback: akun TikTok berhasil disimpan', [
            'account_id'  => $account->id,
            'seller_name' => $account->seller_name,
            'shop_name'   => $account->shop_name,
        ]);

        return response()->json([
            'message'    => 'Akun TikTok berhasil dihubungkan!',
            'account_id' => $account->id,
            'shop_name'  => $account->shop_name,
        ], 200);
    }

    /**
     * Ambil info shop yang diauthorize dari TikTok API
     */
    private function getAuthorizedShop(string $accessToken): array
    {
        try {
            $appKey    = config('services.tiktok.app_key');
            $appSecret = config('services.tiktok.app_secret');
            $timestamp = time();

            // Generate signature
            $params = [
                'app_key'      => $appKey,
                'timestamp'    => $timestamp,
                'access_token' => $accessToken,
            ];

            ksort($params);
            $signStr = $appSecret;
            foreach ($params as $k => $v) {
                $signStr .= $k . $v;
            }
            $signStr .= $appSecret;
            $sign = hash_hmac('sha256', $signStr, $appSecret);

            $url = config('services.tiktok.api_base')
                . '/authorization/202309/shops'
                . '?' . http_build_query(array_merge($params, ['sign' => $sign]));

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_HTTPHEADER     => [
                    'x-tts-access-token: ' . $accessToken,
                    'Content-Type: application/json',
                ],
            ]);

            $raw  = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($raw, true);

            // Ambil shop pertama
            $shop = $data['data']['shops'][0] ?? [];

            return [
                'shop_id'     => $shop['id']          ?? null,
                'shop_name'   => $shop['name']         ?? null,
                'shop_cipher' => $shop['cipher']       ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('getAuthorizedShop error: ' . $e->getMessage());
            return [];
        }
    }
}
