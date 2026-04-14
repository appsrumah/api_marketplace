<?php

namespace App\Services;

use App\Models\ShopeeOrder;
use App\Services\WablasService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ShopeePosOrderService — Kirim data order Shopee ke database POS
 *
 * Alur (persis dengan PosOrderService untuk TikTok):
 *   1. Cek apakah order sudah ada di tabel `so`
 *   2. INSERT ke tabel `so` (Sales Order)
 *   3. Loop items → cari produk berdasarkan seller_sku (model_sku/item_sku) → INSERT ke `so_detail`
 *   4. Update subtotal & grandtotal di `so`
 *   5. Update flag is_synced_to_pos di tabel shopee_orders Laravel
 */
class ShopeePosOrderService
{
    public function __construct(
        private WablasService $wablas = new WablasService()
    ) {}

    private function pos()
    {
        return DB::connection('pos');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Push satu order Shopee → POS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{success: bool, id_so: int|string|null, message: string}
     */
    public function pushOrderToPos(ShopeeOrder $order): array
    {
        // Sudah pernah di-push sebelumnya?
        if ($order->is_synced_to_pos) {
            return [
                'success' => false,
                'id_so'   => $order->pos_order_id,
                'message' => "Order sudah pernah di-push ke POS (SO ID: {$order->pos_order_id})",
            ];
        }

        // Skip status tertentu
        if (in_array($order->order_status, ['UNPAID', 'CANCELLED', 'IN_CANCEL'])) {
            return [
                'success' => false,
                'id_so'   => null,
                'message' => "Order di-skip karena status: {$order->order_status}",
            ];
        }

        // Wajib ada id_outlet dari warehouse agar SO masuk ke outlet yang benar
        // ChannelAccount tidak punya id_outlet langsung, ambil dari warehouse
        $account  = $order->account;
        $idOutlet = 0;

        // Cek apakah ChannelAccount punya kolom id_outlet atau ambil dari warehouse
        if (!empty($account->extra_credentials['id_outlet'])) {
            $idOutlet = (int) $account->extra_credentials['id_outlet'];
        } elseif ($account->warehouse && $account->warehouse->id_outlet) {
            $idOutlet = (int) $account->warehouse->id_outlet;
        }

        if ($idOutlet === 0) {
            return [
                'success' => false,
                'id_so'   => null,
                'message' => 'Akun Shopee belum memiliki ID Outlet. Atur di halaman Integrasi → edit extra_credentials.id_outlet terlebih dahulu.',
            ];
        }

        $shopName = $account->shop_name ?? 'Shopee';

        // Cek apakah order sudah ada di POS (tanpa flag is_synced_to_pos)
        $existing = $this->pos()->table('so')->where('nomor_so', $order->order_sn)->first();
        if ($existing) {
            $order->update([
                'is_synced_to_pos' => true,
                'synced_to_pos_at' => now(),
                'pos_order_id'     => (string) $existing->id,
            ]);

            return [
                'success' => false,
                'id_so'   => $existing->id,
                'message' => "Order sudah ada di POS (SO ID: {$existing->id}) — flag diperbarui.",
            ];
        }

        try {
            $this->pos()->beginTransaction();

            // ── INSERT ke tabel `so` ──────────────────────────────────────────
            $createTime = $order->create_time
                ? \Carbon\Carbon::createFromTimestamp($order->create_time)->format('Y-m-d H:i:s')
                : now()->format('Y-m-d H:i:s');

            $idSo = $this->pos()->table('so')->insertGetId([
                'nomor_so'       => $order->order_sn,
                'tanggal_so'     => now()->toDateString(),
                'id_customer'    => 0,
                'id_salesman'    => 0,
                'id_kurir'       => 0,
                'is_ppn'         => 'N',
                'id_tipe_bayar'  => 0,
                'is_bon'         => 1,
                'tokped_invoice' => $order->order_sn,
                'no_resi'        => $order->tracking_number ?? '',
                'id_outlet'      => $idOutlet,
                'keterangan'     => $shopName . ' | ' . ($order->buyer_name ?? ''),
                'pembeli'        => $order->buyer_name ?? '',
                'create_time'    => $createTime,
            ]);

            // ── Loop items → cari produk di POS → INSERT so_detail ───────────
            $subtotal     = 0;
            $skippedItems = 0;

            foreach ($order->items as $item) {
                // Gunakan model_sku (variasi) jika ada, fallback ke item_sku
                $sku = trim($item->model_sku ?: $item->item_sku ?: '');

                if ($sku === '') {
                    Log::warning('ShopeePosOrderService: item tanpa SKU', [
                        'order_sn'   => $order->order_sn,
                        'item_id'    => $item->item_id ?? null,
                        'item_name'  => $item->item_name ?? null,
                    ]);
                    $skippedItems++;
                    continue;
                }

                // Cari produk di POS berdasarkan seller_sku = nomor_product
                $product = $this->pos()->table('product')
                    ->where('id_outlet', $idOutlet)
                    ->where('nomor_product', $sku)
                    ->where('is_aktif', '1')
                    ->first();

                if (!$product) {
                    Log::warning("ShopeePosOrderService: SKU '{$sku}' tidak ditemukan di POS outlet {$idOutlet}");
                    $skippedItems++;
                    continue;
                }

                // Harga: pakai discounted_price jika ada, fallback original_price, fallback harga POS
                $harga = (float) (
                    ($item->model_discounted_price ?? 0) > 0
                    ? $item->model_discounted_price
                    : (($item->model_original_price ?? 0) > 0
                        ? $item->model_original_price
                        : ($product->harga ?? 0))
                );
                $qty = (int) ($item->quantity_purchased ?? 1);

                $this->pos()->table('so_detail')->insert([
                    'id_so'          => $idSo,
                    'id_product'     => $product->id,
                    'qty'            => $qty,
                    'harga'          => $harga,
                    'modal'          => $product->modal ?? 0,
                    'harga_reseller' => $product->harga_reseller ?? 0,
                    'keterangan'     => $order->buyer_name ?? '',
                ]);

                $subtotal += $harga * $qty;
            }

            // ── Update subtotal & grandtotal di SO ────────────────────────────
            $this->pos()->table('so')->where('id', $idSo)->update([
                'subtotal'   => $subtotal,
                'grandtotal' => $subtotal,
            ]);

            $this->pos()->commit();

            // ── Tandai di tabel shopee_orders Laravel ─────────────────────────
            $order->update([
                'is_synced_to_pos' => true,
                'synced_to_pos_at' => now(),
                'pos_order_id'     => (string) $idSo,
            ]);

            // ── Kirim notifikasi WhatsApp via Wablas ──────────────────────────
            try {
                $this->sendWablasNotification($order, $idSo, $subtotal);
            } catch (\Throwable $notifErr) {
                Log::warning('ShopeePosOrderService: Gagal kirim notif WA', [
                    'order_sn' => $order->order_sn,
                    'error'    => $notifErr->getMessage(),
                ]);
            }

            $message = "Berhasil push ke POS — SO ID: {$idSo}, subtotal: Rp " . number_format($subtotal, 0, ',', '.');
            if ($skippedItems > 0) {
                $message .= " ({$skippedItems} item dilewati — SKU tidak ditemukan di POS)";
            }

            return [
                'success' => true,
                'id_so'   => $idSo,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            $this->pos()->rollBack();

            Log::error('ShopeePosOrderService::pushOrderToPos gagal', [
                'order_sn' => $order->order_sn,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'id_so'   => null,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Batch push (maks 50 order sekaligus)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param  iterable<ShopeeOrder>  $orders  (sudah di-load dengan 'items' + 'account')
     * @return array{success: int, skipped: int, failed: int}
     */
    public function pushBatchToPos(iterable $orders): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($orders as $order) {
            $r = $this->pushOrderToPos($order);

            if ($r['success']) {
                $result['success']++;
            } elseif (
                str_contains($r['message'], 'sudah ada')    ||
                str_contains($r['message'], 'sudah pernah') ||
                str_contains($r['message'], 'di-skip')
            ) {
                $result['skipped']++;
            } else {
                $result['failed']++;
                Log::warning('ShopeePosOrderService batch: 1 order gagal', [
                    'order_sn' => $order->order_sn,
                    'message'  => $r['message'],
                ]);
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Kirim notifikasi WA untuk order Shopee (mirip TikTok)
    // ─────────────────────────────────────────────────────────────────────────
    private function sendWablasNotification(ShopeeOrder $order, int $idSo, float $subtotal): void
    {
        // Cari nomor notif dari extra_credentials akun
        $phone = $order->account?->extra_credentials['telp_notif'] ?? '';

        if (empty(trim($phone))) {
            return;
        }

        $shopName   = $order->account?->shop_name ?? 'Shopee';
        $buyerName  = $order->buyer_name ?? 'Pembeli';
        $itemCount  = $order->items->count();
        $rp         = number_format($subtotal, 0, ',', '.');

        $message = "📦 *Order Baru Shopee*\n"
            . "Toko: {$shopName}\n"
            . "Order: {$order->order_sn}\n"
            . "Pembeli: {$buyerName}\n"
            . "Jumlah Item: {$itemCount}\n"
            . "Total: Rp {$rp}\n"
            . "SO POS ID: {$idSo}\n"
            . "Status: {$order->order_status}";

        $phones = array_filter(array_map('trim', explode(',', $phone)));
        foreach ($phones as $p) {
            $this->wablas->send($p, $message);
        }
    }
}
