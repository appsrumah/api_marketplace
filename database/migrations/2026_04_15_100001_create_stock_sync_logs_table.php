<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_sync_logs', function (Blueprint $table) {
            $table->id();

            // ── Akun & Platform ──────────────────────────────────────
            $table->unsignedBigInteger('account_id');
            $table->string('platform', 20)->index();          // SHOPEE, TIKTOK, TOKOPEDIA
            $table->string('account_name')->nullable();        // seller_name (denormalized)

            // ── Produk ───────────────────────────────────────────────
            $table->string('product_id');                       // item_id / product_id
            $table->string('sku_id')->nullable();               // model_id / sku_id
            $table->string('seller_sku')->nullable();           // nomor_product di POS
            $table->string('title')->nullable();                // nama produk (ringkasan log)

            // ── Stok ─────────────────────────────────────────────────
            $table->integer('old_quantity')->default(0);        // qty sebelumnya di produk_saya
            $table->integer('pos_stock')->default(0);           // current_stock dari POS
            $table->integer('pushed_stock')->default(0);        // qty yang dikirim ke API

            // ── Hasil ────────────────────────────────────────────────
            $table->string('status', 20)->default('pending');   // success, failed, skipped
            $table->text('error_message')->nullable();          // pesan error jika gagal
            $table->json('api_response')->nullable();           // response asli dari API

            // ── Retry info ───────────────────────────────────────────
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            // ── Waktu ────────────────────────────────────────────────
            $table->timestamp('synced_at')->useCurrent();
            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────
            $table->index(['account_id', 'platform']);
            $table->index('status');
            $table->index('synced_at');
            $table->index(['product_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sync_logs');
    }
};
