<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ChannelAccount;
use App\Models\MarketplaceChannel;
use App\Services\ShopeeApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopeeAuthController extends Controller
{
    public function __construct(
        private ShopeeApiService $shopeeService
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

            // ── STEP 3: Cari channel Shopee ───────────────────────────────
            $channel = MarketplaceChannel::where('code', MarketplaceChannel::SHOPEE)
                ->orWhere('slug', 'shopee')
                ->first();

            if (!$channel) {
                throw new \RuntimeException(
                    'Channel Shopee tidak ditemukan. Jalankan DatabaseSeeder atau tambah manual di tabel marketplace_channels.'
                );
            }

            // ── STEP 4: Simpan / update di channel_accounts ───────────────
            $userId          = session('shopee_connect_user_id') ?? Auth::id();
            $expireIn        = (int) ($tokenData['expire_in'] ?? 14400);         // default 4 jam
            $refreshExpireIn = (int) ($tokenData['refresh_token_expire_in'] ?? 2592000); // default 30 hari

            /** @var ChannelAccount $account */
            $account = ChannelAccount::updateOrCreate(
                [
                    'channel_id' => $channel->id,
                    'shop_id'    => (string) $shopId,
                ],
                [
                    'user_id'                  => $userId,
                    'shop_name'                => $shopName ?? ('Toko Shopee #' . $shopId),
                    'seller_name'              => $shopName,
                    'region'                   => 'ID',
                    'access_token'             => $tokenData['access_token'],
                    'access_token_expires_at'  => now()->addSeconds($expireIn),
                    'refresh_token'            => $tokenData['refresh_token'] ?? null,
                    'refresh_token_expires_at' => now()->addSeconds($refreshExpireIn),
                    'status'                   => 'active',
                    'token_obtained_at'        => now(),
                ]
            );

            // Bersihkan session
            session()->forget('shopee_connect_user_id');

            ActivityLog::record(
                'integration.shopee_connect',
                "Terhubung ke Shopee toko \"{$account->shop_name}\" (shop_id: {$shopId})"
            );

            Log::info("✅ Shopee akun tersimpan: id={$account->id}, shop={$account->shop_name}, shop_id={$shopId}");

            return redirect()->route('integrations.index')
                ->with('success', "✅ Akun Shopee \"{$account->shop_name}\" berhasil terhubung!");
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
    public function refreshToken(ChannelAccount $account)
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
                'access_token'             => $tokenData['access_token'],
                'access_token_expires_at'  => now()->addSeconds($expireIn),
                'refresh_token'            => $tokenData['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expires_at' => now()->addSeconds($refreshExpireIn),
                'token_obtained_at'        => now(),
                'status'                   => 'active',
            ]);

            ActivityLog::record(
                'integration.shopee_refresh_token',
                "Refresh token Shopee akun \"{$account->shop_name}\""
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
    public function disconnect(ChannelAccount $account)
    {
        $this->authorizeAccount($account);

        $name = $account->shop_name ?? $account->seller_name ?? ('Toko #' . $account->shop_id);

        ActivityLog::record('integration.shopee_disconnect', "Memutuskan Shopee \"{$name}\"");

        $account->update(['status' => 'disconnected']);

        return redirect()->route('integrations.index')
            ->with('success', "Akun Shopee \"{$name}\" berhasil diputuskan.");
    }

    /* ===================================================================
     *  PRIVATE: Cek otorisasi user ke akun ini
     * =================================================================== */
    private function authorizeAccount(ChannelAccount $account): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $account->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke akun ini.');
        }
    }
}
