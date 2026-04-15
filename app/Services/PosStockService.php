<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosStockService
{
    /**
     * Ambil stok 1 SKU dari tabel product_stocks di DB POS.
     * Query langsung ke kolom current_stock berdasarkan nomor_product + outlet_id.
     *
     * @param  string  $sellerSku   = nomor_product di tabel product_stocks
     * @param  int     $idOutlet    = outlet_id di tabel product_stocks
     * @return int     current_stock (minimum 0)
     */
    public function getStock(string $sellerSku, int $idOutlet): int
    {
        try {
            $row = DB::connection('pos')->selectOne(
                "SELECT current_stock
                FROM product_stocks
                WHERE outlet_id     = ?
                  AND nomor_product = ?
                LIMIT 1",
                [$idOutlet, trim($sellerSku)]
            );

            if (! $row) {
                Log::warning('PosStockService: SKU tidak ditemukan di product_stocks', [
                    'seller_sku' => $sellerSku,
                    'outlet_id'  => $idOutlet,
                ]);
                return 0;
            }

            return max(0, (int) $row->current_stock);
        } catch (\Throwable $e) {
            Log::error('PosStockService::getStock error', [
                'seller_sku' => $sellerSku,
                'outlet_id'  => $idOutlet,
                'error'      => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Ambil stok untuk banyak SKU sekaligus dari tabel product_stocks (1 query ke DB POS).
     * Lebih efisien dibanding loop getStock().
     *
     * @param  string[]  $sellerSkus  nomor_product di tabel product_stocks
     * @param  int       $idOutlet    outlet_id di tabel product_stocks
     * @return array<string, int>     ['SKU001' => 15, 'SKU002' => 0, ...]
     */
    public function getStockBulk(array $sellerSkus, int $idOutlet): array
    {
        if (empty($sellerSkus)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($sellerSkus), '?'));

            $rows = DB::connection('pos')->select(
                "SELECT nomor_product AS sku, current_stock
                FROM product_stocks
                WHERE outlet_id      = ?
                  AND nomor_product IN ({$placeholders})",
                array_merge([$idOutlet], array_map('trim', $sellerSkus))
            );

            $stockMap = [];
            foreach ($rows as $row) {
                $stockMap[$row->sku] = max(0, (int) $row->current_stock);
            }

            // SKU yang tidak ditemukan di POS → 0
            foreach ($sellerSkus as $sku) {
                if (! isset($stockMap[trim($sku)])) {
                    $stockMap[trim($sku)] = 0;
                    Log::warning('PosStockService: SKU tidak ditemukan di product_stocks (bulk)', [
                        'seller_sku' => $sku,
                        'outlet_id'  => $idOutlet,
                    ]);
                }
            }

            return $stockMap;
        } catch (\Throwable $e) {
            Log::error('PosStockService::getStockBulk error', [
                'outlet_id' => $idOutlet,
                'error'     => $e->getMessage(),
            ]);

            // Fallback: semua 0 agar job tidak meledak
            return array_fill_keys(array_map('trim', $sellerSkus), 0);
        }
    }
}
