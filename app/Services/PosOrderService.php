<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PosOrderService — Kirim data order TikTok ke database POS
 *
 * Alur (sama persis dengan logika Shopee lama di get_order_list.php):
 *   1. Cek apakah order sudah ada di tabel `so`
 *   2. INSERT ke tabel `so` (Sales Order)
 *   3. Loop items → cari produk berdasarkan seller_sku → INSERT ke `so_detail`
 *   4. Update subtotal & grandtotal di `so`
 *   5. Update flag is_synced_to_pos di tabel orders Laravel
 */
class PosOrderService
{
    private function pos()
    {
        return DB::connection('pos');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Push satu order → POS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array{success: bool, id_so: int|string|null, message: string}
     */
    public function pushOrderToPos(Order $order): array
    {
        // Sudah pernah di-push sebelumnya?
        if ($order->is_synced_to_pos) {
            return [
                'success' => false,
                'id_so'   => $order->pos_order_id,
                'message' => "Order sudah pernah di-push ke POS (SO ID: {$order->pos_order_id})",
            ];
        }

        // Skip status tertentu — sama seperti logika Shopee lama
        if (in_array($order->order_status, ['UNPAID', 'CANCELLED'])) {
            return [
                'success' => false,
                'id_so'   => null,
                'message' => "Order di-skip karena status: {$order->order_status}",
            ];
        }

        // Wajib ada id_outlet agar SO masuk ke outlet yang benar
        $idOutlet = (int) ($order->account?->id_outlet ?? 0);
        if ($idOutlet === 0) {
            return [
                'success' => false,
                'id_so'   => null,
                'message' => 'Akun belum memiliki ID Outlet. Atur di halaman Integrasi → Edit akun terlebih dahulu.',
            ];
        }

        $shopName = $order->account?->shop_name ?? 'TikTok Shop';

        // Cek apakah order sudah ada di POS (tanpa flag is_synced_to_pos)
        $existing = $this->pos()->table('so')->where('nomor_so', $order->order_id)->first();
        if ($existing) {
            // Sync flag Laravel supaya tidak perlu dicek lagi berikutnya
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
            $createTime = $order->tiktok_create_time
                ? \Carbon\Carbon::createFromTimestamp($order->tiktok_create_time)->format('Y-m-d H:i:s')
                : now()->format('Y-m-d H:i:s');

            $idSo = $this->pos()->table('so')->insertGetId([
                'nomor_so'       => $order->order_id,
                'tanggal_so'     => now()->toDateString(),
                'id_customer'    => 0,
                'id_salesman'    => 0,
                'id_kurir'       => 0,
                'is_ppn'         => 'N',
                'id_tipe_bayar'  => 0,
                'is_bon'         => 1,
                'tokped_invoice' => $order->order_id,
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
                $sku = trim($item->seller_sku ?? '');

                if ($sku === '') {
                    Log::warning('PosOrderService: item tanpa seller_sku', [
                        'order_id'     => $order->order_id,
                        'sku_id'       => $item->sku_id ?? null,
                        'product_name' => $item->product_name ?? null,
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
                    Log::warning("PosOrderService: SKU '{$sku}' tidak ditemukan di POS outlet {$idOutlet}");
                    $skippedItems++;
                    continue;
                }

                // Gunakan original_price saja jika tersedia, fallback ke harga POS
                $harga = (float) (
                    ($item->original_price ?? 0) > 0
                        ? $item->original_price
                        : ($product->harga ?? 0)
                );
                $qty = (int) ($item->quantity ?? 1);

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

            // ── Tandai di tabel orders Laravel ───────────────────────────────
            $order->update([
                'is_synced_to_pos' => true,
                'synced_to_pos_at' => now(),
                'pos_order_id'     => (string) $idSo,
            ]);

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

            Log::error('PosOrderService::pushOrderToPos gagal', [
                'order_id' => $order->order_id,
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
     * @param  iterable<Order>  $orders  (sudah di-load dengan 'items' + 'account')
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
                Log::warning('PosOrderService batch: 1 order gagal', [
                    'order_id' => $order->order_id,
                    'message'  => $r['message'],
                ]);
            }
        }

        return $result;
    }
}
