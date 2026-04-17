<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ShopeeOrder;

class OrderMessageBuilder
{
    public function buildShopeeMessage(ShopeeOrder $order, int $idSo, float $subtotal): string
    {
        $shopName  = $order->account?->seller_name ?? 'Shopee';
        $buyerName = $order->buyer_name ?? 'Pembeli';

        $lines = [];
        $lines[] = "📦*Order Baru Shopee*📦";
        $lines[] = "Toko: {$shopName}";
        $lines[] = "Order: {$order->order_sn}";
        $lines[] = "Pembeli: {$buyerName}";
        $lines[] = "Jumlah Item: {$order->items->count()}";
        $lines[] = "";

        foreach ($order->items as $item) {
            $name = $item->item_name ?? $item->model_name ?? 'Produk';
            $sku  = $item->effective_sku ?? trim($item->model_sku ?? $item->item_sku ?? '');
            $orig = (float) ($item->model_original_price ?? 0);
            $qty  = (int) ($item->quantity_purchased ?? 1);
            $linePrice = number_format($orig, 0, ',', '.');
            $lineSubtotal = number_format(round($orig * $qty, 0), 0, ',', '.');

            $lines[] = "• {$name}" . ($sku ? " (SKU: {$sku})" : '');
            $lines[] = "  Harga: Rp {$linePrice} × {$qty} = Rp {$lineSubtotal}";
        }

        $lines[] = "";
        $lines[] = "Total: Rp " . number_format($subtotal, 0, ',', '.');

        $shippingCarrier = $order->shipping_carrier ?? ($order->shipping_provider ?? '');
        if (!empty($shippingCarrier)) {
            $lines[] = "Pengiriman: {$shippingCarrier}";
        }
        if (!empty($order->shipping_fee) && $order->shipping_fee > 0) {
            $lines[] = "Ongkir: Rp " . number_format($order->shipping_fee, 0, ',', '.');
        }

        if (!empty($order->tracking_number)) {
            $lines[] = "Resi: {$order->tracking_number}";
        }

        $lines[] = "SO POS ID: {$idSo}";
        $lines[] = "Status: {$order->order_status}";

        return implode("\n", $lines);
    }

    public function buildTikTokMessage(Order $order, int $idSo, float $subtotal): string
    {
        $shopName  = $order->account?->shop_name ?? 'TikTok Shop';
        $buyerName = $order->buyer_name ?? '-';

        $lines = [];
        $lines[] = "🎵*ADA ORDER BARU TIKTOK*🎵";
        $lines[] = "*{$shopName}*";
        $lines[] = "Order ID  : {$order->order_id}";
        $lines[] = "Pembeli   : {$buyerName}";
        $lines[] = "SO ID POS : {$idSo}";
        $lines[] = "";

        foreach ($order->items as $item) {
            $nama  = $item->product_name ?? $item->seller_sku ?? 'Produk';
            $harga = number_format((float) ($item->original_price ?? 0), 0, ',', '.');
            $qty   = (int) ($item->quantity ?? 1);
            $lines[] = "• {$nama}";
            $lines[] = "  Rp {$harga} × {$qty} pcs";
        }

        $lines[] = "";
        $lines[] = "Total     : Rp " . number_format($subtotal, 0, ',', '.');

        if (!empty($order->tracking_number)) {
            $lines[] = "Resi      : {$order->tracking_number}";
        }

        return implode("\n", $lines);
    }
}
