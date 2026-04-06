<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosStockService
{
    /**
     * Ambil stok realtime 1 SKU dari DB POS.
     * Logika: total_po - total_so (sama persis dengan update_stok_shop.php Shopee)
     *
     * @param  string  $sellerSku   = nomor_product di tabel product POS
     * @param  int     $idOutlet    = id_outlet di tabel product POS
     * @return int     stok (minimum 0)
     */
    public function getStock(string $sellerSku, int $idOutlet): int
    {
        try {
            $row = DB::connection('pos')->selectOne(
                "SELECT
                    a.id,
                    COALESCE(
                        (SELECT SUM(c.qty) FROM po_detail c WHERE c.id_product = a.id),
                        0
                    ) AS total_po,
                    COALESCE(
                        (SELECT SUM(b.qty) FROM so_detail b WHERE b.id_product = a.id),
                        0
                    ) AS total_so
                FROM product a
                WHERE a.id_outlet = ?
                  AND a.is_aktif  = '1'
                  AND a.nomor_product = ?
                LIMIT 1",
                [$idOutlet, trim($sellerSku)]
            );

            if (! $row) {
                Log::warning('PosStockService: SKU tidak ditemukan di POS', [
                    'seller_sku' => $sellerSku,
                    'id_outlet'  => $idOutlet,
                ]);
                return 0;
            }

            $qty = (int) $row->total_po - (int) $row->total_so;

            return max(0, $qty); // jika negatif → 0, sama seperti Shopee lama
        } catch (\Throwable $e) {
            Log::error('PosStockService::getStock error', [
                'seller_sku' => $sellerSku,
                'id_outlet'  => $idOutlet,
                'error'      => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Ambil stok untuk banyak SKU sekaligus (1 query ke DB POS).
     * Lebih efisien dibanding loop getStock().
     *
     * @param  string[]  $sellerSkus
     * @param  int       $idOutlet
     * @return array<string, int>   ['SKU001' => 15, 'SKU002' => 0, ...]
     */
    public function getStockBulk(array $sellerSkus, int $idOutlet): array
    {
        if (empty($sellerSkus)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($sellerSkus), '?'));

            $rows = DB::connection('pos')->select(
                "SELECT
                    a.nomor_product AS sku,
                    COALESCE(
                        (SELECT SUM(c.qty) FROM po_detail c WHERE c.id_product = a.id),
                        0
                    ) AS total_po,
                    COALESCE(
                        (SELECT SUM(b.qty) FROM so_detail b WHERE b.id_product = a.id),
                        0
                    ) AS total_so
                FROM product a
                WHERE a.id_outlet = ?
                  AND a.is_aktif  = '1'
                  AND a.nomor_product IN ({$placeholders})",
                array_merge([$idOutlet], array_map('trim', $sellerSkus))
            );

            $stockMap = [];
            foreach ($rows as $row) {
                $qty = (int) $row->total_po - (int) $row->total_so;
                $stockMap[$row->sku] = max(0, $qty);
            }

            // SKU yang tidak ditemukan di POS → 0
            foreach ($sellerSkus as $sku) {
                if (! isset($stockMap[trim($sku)])) {
                    $stockMap[trim($sku)] = 0;
                    Log::warning('PosStockService: SKU tidak ditemukan di POS (bulk)', [
                        'seller_sku' => $sku,
                        'id_outlet'  => $idOutlet,
                    ]);
                }
            }

            return $stockMap;
        } catch (\Throwable $e) {
            Log::error('PosStockService::getStockBulk error', [
                'id_outlet' => $idOutlet,
                'error'     => $e->getMessage(),
            ]);

            // Fallback: semua 0 agar job tidak meledak
            return array_fill_keys(array_map('trim', $sellerSkus), 0);
        }
    }
}
