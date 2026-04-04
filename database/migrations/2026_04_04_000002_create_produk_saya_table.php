<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_saya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')
                ->constrained('account_shop_tiktok')
                ->cascadeOnDelete();
            $table->string('product_id', 50)->comment('products->id');
            $table->string('sku_id', 50)->comment('skus->id varian');
            $table->string('platform', 30)->comment('TOKOPEDIA / TIKTOK');
            $table->string('title')->comment('Nama Produk');
            $table->string('product_status', 30)->comment('Status produk: ACTIVATE etc');
            $table->integer('quantity')->default(0)->comment('inventory->quantity');
            $table->decimal('price', 15, 2)->default(0)->comment('tax_exclusive_price');
            $table->string('seller_sku', 100)->nullable()->comment('SKU POS');
            $table->string('status_info', 30)->nullable()->comment('status_info->status');
            $table->string('current_status', 30)->nullable()->comment('status saat ini');
            $table->timestamps();

            $table->index('account_id');
            $table->index('product_id');
            $table->index('platform');
            $table->unique(['account_id', 'sku_id', 'platform'], 'unique_sku_platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_saya');
    }
};
