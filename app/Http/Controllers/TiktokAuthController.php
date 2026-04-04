<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ProdukSaya;
use App\Services\TiktokApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiktokAuthController extends Controller
{
    public function __construct(
        private TiktokApiService $tiktokService
    ) {}

    /* ---------- Redirect to TikTok OAuth ---------- */
    public function redirect()
    {
        return redirect()->away($this->tiktokService->getAuthUrl());
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
                    'access_token_expire_in'  => $now->copy()->addSeconds($tokenData['access_token_expire_in'] ?? 0),
                    'refresh_token'           => $tokenData['refresh_token']            ?? '',
                    'refresh_token_expire_in' => $now->copy()->addSeconds($tokenData['refresh_token_expire_in'] ?? 0),
                    'shop_id'                 => $shopId,
                    'shop_name'               => $shopName,
                    'shop_cipher'             => $shopCipher,
                    'status'                  => 'active',
                    'token_obtained_at'       => $now,
                ]
            );

            Log::info("✅ Akun TikTok disimpan: id={$account->id}, seller={$account->seller_name}");

            // ======== STEP 4: Auto sync semua produk ========
            if ($account->shop_cipher) {
                $result = $this->tiktokService->fetchAllProducts(
                    $account->access_token,
                    $account->shop_cipher
                );

                $saved = $this->saveProducts($account->id, $result['products']);

                $account->update(['last_sync_at' => now()]);

                Log::info("✅ Produk tersimpan: {$saved}, halaman: {$result['total_pages']}, duplikat: {$result['total_skipped']}");

                return redirect()->route('dashboard')->with(
                    'success',
                    "✅ Otorisasi berhasil! Akun \"{$account->seller_name}\" terhubung. " .
                        "{$saved} produk disimpan dari {$result['total_pages']} halaman."
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
            if ($account->isTokenExpired()) {
                // Try refresh
                $newTokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);
                $refreshNow = now();
                $account->update([
                    'access_token'            => $newTokenData['access_token'] ?? $account->access_token,
                    'access_token_expire_in'  => isset($newTokenData['access_token_expire_in'])
                        ? $refreshNow->copy()->addSeconds($newTokenData['access_token_expire_in'])
                        : $account->access_token_expire_in,
                    'refresh_token'           => $newTokenData['refresh_token'] ?? $account->refresh_token,
                    'refresh_token_expire_in' => isset($newTokenData['refresh_token_expire_in'])
                        ? $refreshNow->copy()->addSeconds($newTokenData['refresh_token_expire_in'])
                        : $account->refresh_token_expire_in,
                    'token_obtained_at'       => $refreshNow,
                ]);
                $account->refresh();
            }

            if (!$account->shop_cipher) {
                $shops = $this->tiktokService->getAuthShop($account->access_token);
                if (!empty($shops)) {
                    $account->update(['shop_cipher' => $shops[0]['cipher'] ?? null]);
                    $account->refresh();
                }
            }

            if (!$account->shop_cipher) {
                return back()->with('error', 'Shop cipher tidak ditemukan.');
            }

            $result = $this->tiktokService->fetchAllProducts(
                $account->access_token,
                $account->shop_cipher
            );

            $saved = $this->saveProducts($account->id, $result['products']);

            return back()->with(
                'success',
                "Sync berhasil! {$saved} produk diperbarui/ditambahkan dari {$result['total_pages']} halaman. " .
                    "Duplikat dilewati: {$result['total_skipped']}."
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

    /* ---------- Save/update products to DB (upsert) ---------- */
    private function saveProducts(int $accountId, array $products): int
    {
        $saved = 0;

        // Use chunked upsert for efficiency
        $chunks = array_chunk($products, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($p) use ($accountId) {
                return [
                    'account_id'     => $accountId,
                    'product_id'     => $p['product_id'],
                    'sku_id'         => $p['sku_id'],
                    'platform'       => $p['platform'],
                    'title'          => $p['title'],
                    'product_status' => $p['product_status'],
                    'quantity'       => $p['quantity'],
                    'price'          => $p['price'],
                    'seller_sku'     => $p['seller_sku'],
                    'status_info'    => $p['status_info'],
                    'current_status' => $p['current_status'],
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ];
            }, $chunk);

            // Upsert: insert or update by unique key (account_id, sku_id, platform)
            ProdukSaya::upsert(
                $rows,
                ['account_id', 'sku_id', 'platform'],
                ['title', 'product_status', 'quantity', 'price', 'seller_sku', 'status_info', 'current_status', 'updated_at']
            );

            $saved += count($chunk);
        }

        return $saved;
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
                'access_token_expire_in'  => now()->addSeconds($validated['access_token_expire_in']),
                'refresh_token'           => $validated['refresh_token'],
                'refresh_token_expire_in' => now()->addSeconds($validated['refresh_token_expire_in']),
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
