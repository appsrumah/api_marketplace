<?php

namespace App\Http\Controllers;

use App\Models\AccountShopShopee;
use App\Models\ActivityLog;
use App\Models\MarketplaceChannel;
use App\Services\ShopeeApiService;
use App\Services\ShopeeProductSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopeeAuthController extends Controller
{
    public function __construct(
        private ShopeeApiService $shopeeService,
        private ShopeeProductSyncService $productSync,
    ) {}

    /* ===================================================================
     *  REDIRECT — Arahkan user ke halaman otorisasi Shopee
     *  GET /shopee/redirect
     * =================================================================== */
    public function redirect()
    {
        if (!$this->shopeeService->isConfigured()) {
            return back()->with('error', 'Konfigurasi Shopee belum lengkap. Periksa SHOPEE_PARTNER_ID dan SHOPEE_PARTNER_KEY di .env');
        }

        // Simpan user context di session agar bisa di-assign saat callback
        session(['shopee_connect_user_id' => Auth::id()]);

        return redirect()->away($this->shopeeService->getAuthUrl());
    }

    /* ===================================================================
     *  CALLBACK — Shopee redirect kembali ke sini setelah otorisasi
     *  GET /shopee/callback?code=xxx&shop_id=xxx
     * =================================================================== */
    public function callback(Request $request)
    {
        // Shopee mengirim: code, shop_id (dan kadang cancel jika ditolak)
        if ($request->has('cancel') || $request->query('code') === null) {
            return redirect()->route('integrations.index')
                ->with('warning', 'Otorisasi Shopee dibatalkan.');
        }

        $code   = $request->query('code');
        $shopId = (int) $request->query('shop_id');

        if (!$code || !$shopId) {
            Log::warning('Shopee callback: code atau shop_id kosong', $request->query());
            return redirect()->route('integrations.index')
                ->with('error', 'Otorisasi Shopee gagal: code atau shop_id tidak ditemukan dalam callback.');
        }

        try {
            // ── STEP 1: Exchange code → access_token ──────────────────────
            $tokenData = $this->shopeeService->getAccessToken($code, $shopId);

            if (empty($tokenData['access_token'])) {
                throw new \RuntimeException(
                    'Shopee tidak mengembalikan access_token. Response: ' . json_encode($tokenData)
                );
            }

            // ── STEP 2: Ambil info toko ───────────────────────────────────
            $shopName = null;
            try {
                $shopInfo = $this->shopeeService->getShopInfo($tokenData['access_token'], $shopId);
                $shopName = $shopInfo['response']['shop_name']
                    ?? $shopInfo['shop_name']
                    ?? $shopInfo['response']['username']
                    ?? null;
            } catch (\Throwable $e) {
                Log::warning('Shopee getShopInfo gagal: ' . $e->getMessage());
            }

            // ── STEP 3: Ambil channel_id Shopee ──────────────────────────
            $channelId = MarketplaceChannel::where('code', MarketplaceChannel::SHOPEE)->value('id');

            // ── STEP 4: Simpan / update di account_shop_shopee ───────────
            $userId          = session('shopee_connect_user_id') ?? Auth::id();
            $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);         // default 4 jam
            $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000); // default 30 hari

            /** @var AccountShopShopee $account */
            $account = AccountShopShopee::updateOrCreate(
                [
                    'shop_id' => (string) $shopId,
                ],
                [
                    'channel_id'              => $channelId,
                    'user_id'                 => $userId,
                    'seller_name'             => $shopName ?? ('Toko Shopee #' . $shopId),
                    'code'                    => $code,
                    'access_token'            => $tokenData['access_token'],
                    'access_token_expire_in'  => now()->addSeconds($expireIn),
                    'refresh_token'           => $tokenData['refresh_token'] ?? null,
                    'refresh_token_expire_in' => now()->addSeconds($refreshExpireIn),
                    'status'                  => 'active',
                    'token_obtained_at'       => now(),
                ]
            );

            // Bersihkan session
            session()->forget('shopee_connect_user_id');

            ActivityLog::record(
                'integration.shopee_connect',
                "Terhubung ke Shopee toko \"{$account->seller_name}\" (shop_id: {$shopId})"
            );

            Log::info("✅ Shopee akun tersimpan: id={$account->id}, toko={$account->seller_name}, shop_id={$shopId}");

            // ── STEP 5: Auto-sync produk Shopee segera setelah integrasi ──
            $syncResult = ['saved' => 0, 'error' => null];
            try {
                $syncResult = $this->productSync->syncForAccount($account);
                Log::info("✅ Auto-sync produk Shopee selesai: {$syncResult['saved']} produk", [
                    'account_id' => $account->id,
                ]);
            } catch (\Throwable $e) {
                Log::warning("⚠ Auto-sync produk Shopee gagal setelah integrasi: " . $e->getMessage());
                $syncResult['error'] = $e->getMessage();
            }

            $syncMsg = $syncResult['saved'] > 0
                ? " {$syncResult['saved']} produk berhasil disinkronisasi."
                : ($syncResult['error'] ? " (Sync produk gagal: {$syncResult['error']})" : '');

            return redirect()->route('integrations.index')
                ->with('success', "✅ Akun Shopee \"{$account->seller_name}\" berhasil terhubung!{$syncMsg}");
        } catch (\Throwable $e) {
            Log::error('Shopee callback error: ' . $e->getMessage(), [
                'code'    => $code,
                'shop_id' => $shopId,
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->route('integrations.index')
                ->with('error', 'Gagal menghubungkan akun Shopee: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  REFRESH TOKEN — Perbarui access token manual
     *  POST /shopee/accounts/{account}/refresh-token
     * =================================================================== */
    public function refreshToken(AccountShopShopee $account)
    {
        $this->authorizeAccount($account);

        try {
            $shopId    = (int) $account->shop_id;
            $tokenData = $this->shopeeService->refreshAccessToken(
                $account->refresh_token,
                $shopId
            );

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

            ActivityLog::record(
                'integration.shopee_refresh_token',
                "Refresh token Shopee akun \"{$account->seller_name}\""
            );

            return back()->with('success', 'Token Shopee berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Shopee refresh token failed', [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal refresh token Shopee: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  DISCONNECT — Putuskan akun Shopee
     *  DELETE /shopee/accounts/{account}/disconnect
     * =================================================================== */
    public function disconnect(AccountShopShopee $account)
    {
        $this->authorizeAccount($account);

        $name = $account->seller_name ?? ('Toko #' . $account->shop_id);

        ActivityLog::record('integration.shopee_disconnect', "Memutuskan Shopee \"{$name}\"");

        $account->update(['status' => 'revoked']);

        return redirect()->route('integrations.index')
            ->with('success', "Akun Shopee \"{$name}\" berhasil diputuskan.");
    }

    /* ===================================================================
     *  SYNC PRODUCTS — Manual trigger sync produk dari Shopee
     *  POST /shopee/accounts/{account}/sync-products
     * =================================================================== */
    public function syncProducts(AccountShopShopee $account)
    {
        try {
            $this->authorizeAccount($account);

            $result = $this->productSync->syncForAccount($account);

            if ($result['error']) {
                return back()->with('warning', "Sync produk Shopee selesai dengan error: {$result['error']}. Produk tersimpan: {$result['saved']}");
            }

            return back()->with('success', "✅ Berhasil sync {$result['saved']} produk dari Shopee \"{$account->seller_name}\".");
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->route('dashboard')->with('error', 'Akses ditolak: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Shopee sync products error", [
                'account_id' => $account->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            return redirect()->route('dashboard')->with('error', 'Gagal sync produk Shopee: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  PRIVATE: Cek otorisasi user ke akun ini
     * =================================================================== */
    private function authorizeAccount(AccountShopShopee $account): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Sesi login tidak valid.');
        }
        if (!$user->isSuperAdmin() && $account->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke akun ini.');
        }
    }
}
