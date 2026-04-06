<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel order_items: detail item per order (line items)
     * Berdasarkan response dari GET /order/202507/orders (order detail)
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('FK ke orders.id (bukan TikTok order_id)');
            $table->string('tiktok_order_id', 50)->comment('TikTok Order ID referensi');

            // ─── Product Info ──────────────────────────────────────────────
            $table->string('product_id', 50)->comment('TikTok Product ID');
            $table->string('product_name', 500)->nullable();
            $table->string('sku_id', 50)->nullable()->comment('TikTok SKU ID');
            $table->string('sku_name', 500)->nullable();
            $table->string('seller_sku', 100)->nullable()->comment('SKU dari seller (match POS)');
            $table->string('product_image', 500)->nullable()->comment('URL gambar produk');

            // ─── Quantity & Price ──────────────────────────────────────────
            $table->integer('quantity')->default(1);
            $table->decimal('original_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->decimal('platform_discount', 15, 2)->default(0);
            $table->decimal('seller_discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

            // ─── Item Status ───────────────────────────────────────────────
            $table->string('item_status', 30)->nullable()
                ->comment('Status per item jika berbeda');
            $table->boolean('is_gift')->default(false);

            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')->on('orders')
                ->cascadeOnDelete();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('seller_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
