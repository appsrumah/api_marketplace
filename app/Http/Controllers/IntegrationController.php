<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\ChannelAccount;
use App\Models\MarketplaceChannel;
use App\Services\TiktokApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    public function __construct(
        private TiktokApiService $tiktokService
    ) {}

    /* ===================================================================
     *  INDEX — Pusat Integrasi: daftar semua channel + akun user
     * =================================================================== */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user     = Auth::user();
        $channels = MarketplaceChannel::active()->get();

        // Ambil akun berdasar hak akses
        $accountsQuery = AccountShopTiktok::with(['channel', 'user', 'warehouse']);

        if (!$user->isSuperAdmin()) {
            $accountsQuery->where('user_id', $user->id);
        }

        $accounts = $accountsQuery->latest()->get();

        // Shopee & channel lain via channel_accounts
        $channelAccountsQuery = ChannelAccount::with(['channel', 'user', 'warehouse']);
        if (!$user->isSuperAdmin()) {
            $channelAccountsQuery->where('user_id', $user->id);
        }
        $channelAccounts = $channelAccountsQuery->latest()->get();

        // Group by channel_id for display
        $accountsByChannel = $accounts->groupBy('channel_id');

        // Stats (gabungkan TikTok + ChannelAccount)
        $allActive  = $accounts->where('status', 'active')->count()
            + $channelAccounts->where('status', 'active')->count();
        $allExpired = $accounts->filter(fn($a) => $a->isTokenExpired())->count()
            + $channelAccounts->filter(fn($a) => $a->isTokenExpired())->count();

        $stats = [
            'total_channels'  => $channels->count(),
            'total_accounts'  => $accounts->count() + $channelAccounts->count(),
            'active_accounts' => $allActive,
            'expired_tokens'  => $allExpired,
        ];

        return view('integrations.index', compact('channels', 'accounts', 'accountsByChannel', 'channelAccounts', 'stats'));
    }

    /* ===================================================================
     *  SHOW — Detail satu akun integrasi
     * =================================================================== */
    public function show(AccountShopTiktok $account)
    {
        $this->authorizeAccount($account);
        $account->load(['channel', 'user', 'warehouse']);

        $productCount = $account->produk()->count();
        $activeProducts = $account->produk()->where('product_status', 'ACTIVATE')->count();

        return view('integrations.show', compact('account', 'productCount', 'activeProducts'));
    }

    /* ===================================================================
     *  CONNECT — Redirect ke TikTok OAuth (assign user_id)
     * =================================================================== */
    public function connect(Request $request, MarketplaceChannel $channel)
    {
        $channelCode = strtoupper($channel->code ?? $channel->slug ?? '');

        // ── Shopee ────────────────────────────────────────────────────────
        if ($channelCode === 'SHOPEE') {
            return redirect()->route('shopee.redirect');
        }

        // ── Channel belum didukung ────────────────────────────────────────
        if ($channelCode !== 'TIKTOK') {
            return back()->with('warning', "Integrasi untuk {$channel->name} belum tersedia. Segera hadir!");
        }

        // ── TikTok ────────────────────────────────────────────────────────
        // Store user_id + channel_id di session untuk dipakai saat callback
        session([
            'integration_user_id'    => Auth::id(),
            'integration_channel_id' => $channel->id,
        ]);

        return redirect()->away($this->tiktokService->getAuthUrl());
    }

    /* ===================================================================
     *  ASSIGN — Setelah callback berhasil, assign user_id & channel_id
     *           Dipanggil dari TiktokAuthController callback
     * =================================================================== */
    public static function assignFromSession(AccountShopTiktok $account): void
    {
        $userId    = session('integration_user_id');
        $channelId = session('integration_channel_id');

        $updates = [];
        if ($userId && !$account->user_id) {
            $updates['user_id'] = $userId;
        }
        if ($channelId && !$account->channel_id) {
            $updates['channel_id'] = $channelId;
        }

        if (!empty($updates)) {
            $account->update($updates);
        }

        // Cleanup session
        session()->forget(['integration_user_id', 'integration_channel_id']);
    }

    /* ===================================================================
     *  UPDATE — Edit info akun (warehouse, outlet, dll)
     * =================================================================== */
    public function update(Request $request, AccountShopTiktok $account)
    {
        $this->authorizeAccount($account);

        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'id_outlet'    => 'nullable|integer',
        ]);

        $account->update($request->only(['warehouse_id', 'id_outlet']));

        $accountName = $account->shop_name ?? $account->seller_name;
        ActivityLog::record('integration.update', "Mengubah pengaturan akun {$accountName}");

        return back()->with('success', 'Pengaturan akun berhasil diperbarui.');
    }

    /* ===================================================================
     *  REFRESH TOKEN — Manual refresh
     * =================================================================== */
    public function refreshToken(AccountShopTiktok $account)
    {
        $this->authorizeAccount($account);

        try {
            $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);

            $account->update([
                'access_token'            => $tokenData['access_token'],
                'access_token_expire_in'  => now()->addSeconds($tokenData['access_token_expire_in'] ?? 0),
                'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expire_in' => now()->addSeconds($tokenData['refresh_token_expire_in'] ?? 0),
                'token_obtained_at'       => now(),
            ]);

            ActivityLog::record('integration.refresh_token', "Refresh token akun {$account->seller_name}");

            return back()->with('success', 'Token berhasil diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Token refresh failed', ['account_id' => $account->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal refresh token: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  DISCONNECT — Putuskan akun
     * =================================================================== */
    public function disconnect(AccountShopTiktok $account)
    {
        $this->authorizeAccount($account);

        $name = $account->shop_name ?? $account->seller_name;

        ActivityLog::record('integration.disconnect', "Memutuskan akun {$name}", $account);

        $account->update(['status' => 'inactive']);

        return redirect()->route('integrations.index')
            ->with('success', "Akun \"{$name}\" berhasil diputuskan.");
    }

    /* ===================================================================
     *  PRIVATE: Authorization check
     * =================================================================== */
    private function authorizeAccount(AccountShopTiktok $account): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $account->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke akun ini.');
        }
    }
}
