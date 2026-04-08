<?php

namespace App\Http\Controllers;

use App\Models\AccountShopTiktok;
use App\Models\ActivityLog;
use App\Models\ProductDetail;
use App\Services\TiktokApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductDetailController extends Controller
{
    public function __construct(
        private TiktokApiService $tiktokService
    ) {}

    /* ===================================================================
     *  SHOW — Detail 1 produk (dari DB, atau fetch dari API jika belum ada)
     * =================================================================== */
    public function show(Request $request, string $productId)
    {
        // Try from DB first
        $detail = ProductDetail::where('product_id', $productId)->first();

        // If forced refresh or not in DB, fetch from API
        if (!$detail || $request->has('refresh')) {
            $account = $this->resolveAccount($request, $productId);

            if (!$account) {
                return back()->with('error', 'Tidak ditemukan akun terkait produk ini.');
            }

            try {
                $detail = $this->fetchAndSave($account, $productId);
            } catch (\Throwable $e) {
                return back()->with('error', 'Gagal mengambil detail produk: ' . $e->getMessage());
            }
        }

        $detail->load('account');

        return view('products.detail', compact('detail'));
    }

    /* ===================================================================
     *  EDIT FORM — Tampilkan form edit produk
     * =================================================================== */
    public function edit(string $productId)
    {
        $detail = ProductDetail::where('product_id', $productId)->firstOrFail();
        $detail->load('account');

        return view('products.edit', compact('detail'));
    }

    /* ===================================================================
     *  UPDATE — Push perubahan ke TikTok API
     * =================================================================== */
    public function update(Request $request, string $productId)
    {
        $detail = ProductDetail::where('product_id', $productId)->firstOrFail();
        $account = AccountShopTiktok::findOrFail($detail->account_id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $accessToken = $this->ensureFreshToken($account);

            $body = [
                'title'       => $request->input('title'),
                'description' => $request->input('description', ''),
            ];

            // Only include SKU price updates if provided
            if ($request->has('skus')) {
                $body['skus'] = $request->input('skus');
            }

            $result = $this->tiktokService->editProduct(
                $accessToken,
                $account->shop_cipher,
                $productId,
                $body
            );

            // Update local record
            $detail->update([
                'title'       => $request->input('title'),
                'description' => $request->input('description', $detail->description),
            ]);

            ActivityLog::record('products.edit', "Mengedit produk {$productId} ({$request->input('title')})");

            return redirect()->route('products.detail', $productId)
                ->with('success', 'Produk berhasil diupdate di TikTok.');
        } catch (\Throwable $e) {
            Log::error('Product edit failed', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Gagal mengedit produk: ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  TEST — Fetch 1 product detail (for testing API)
     * =================================================================== */
    public function testFetchOne(AccountShopTiktok $account, string $productId)
    {
        try {
            $accessToken = $this->ensureFreshToken($account);

            $result = $this->tiktokService->getProductDetail(
                $accessToken,
                $account->shop_cipher,
                $productId
            );

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil detail produk dari TikTok API.',
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ===================================================================
     *  PRIVATE: Fetch product detail from API & save to DB
     * =================================================================== */
    private function fetchAndSave(AccountShopTiktok $account, string $productId): ProductDetail
    {
        $accessToken = $this->ensureFreshToken($account);

        $apiData = $this->tiktokService->getProductDetail(
            $accessToken,
            $account->shop_cipher,
            $productId
        );

        $category = $apiData['category_chains'] ?? [];
        $lastCat  = end($category);
        $packaging = $apiData['package_dimensions'] ?? [];

        return ProductDetail::updateOrCreate(
            [
                'product_id' => $productId,
                'account_id' => $account->id,
            ],
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
    }

    /* ===================================================================
     *  PRIVATE: Resolve account from request or from DB relation
     * =================================================================== */
    private function resolveAccount(Request $request, string $productId): ?AccountShopTiktok
    {
        if ($request->filled('account_id')) {
            return AccountShopTiktok::find($request->account_id);
        }

        // Try to find from existing product detail
        $existing = ProductDetail::where('product_id', $productId)->first();
        if ($existing) {
            return AccountShopTiktok::find($existing->account_id);
        }

        // Try to find from produk_saya
        $produk = \App\Models\ProdukSaya::where('product_id', $productId)->first();
        if ($produk) {
            return AccountShopTiktok::find($produk->account_id);
        }

        // Fallback: first active account
        return AccountShopTiktok::where('status', 'active')->first();
    }

    /* ===================================================================
     *  PRIVATE: Ensure fresh access token
     * =================================================================== */
    private function ensureFreshToken(AccountShopTiktok $account): string
    {
        if ($account->access_token_expire_in && now()->gte($account->access_token_expire_in)) {
            Log::info("Refreshing expired token for account {$account->id}");

            $tokenData = $this->tiktokService->refreshAccessToken($account->refresh_token);

            $account->update([
                'access_token'            => $tokenData['access_token'],
                'access_token_expire_in'  => now()->addSeconds($tokenData['access_token_expire_in'] ?? 0),
                'refresh_token'           => $tokenData['refresh_token'] ?? $account->refresh_token,
                'refresh_token_expire_in' => now()->addSeconds($tokenData['refresh_token_expire_in'] ?? 0),
                'token_obtained_at'       => now(),
            ]);

            $account->refresh();
        }

        return $account->access_token;
    }
}
