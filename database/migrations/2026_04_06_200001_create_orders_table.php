<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel orders: menyimpan data pesanan dari TikTok Shop API
     * Berdasarkan response dari POST /order/202309/orders/search
     * + GET /order/202507/orders (detail)
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->comment('FK ke account_shop_tiktok');
            $table->unsignedBigInteger('channel_id')->nullable()->comment('FK ke marketplace_channels');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('FK ke warehouses');

            // ─── Identitas Order ───────────────────────────────────────────
            $table->string('order_id', 50)->comment('TikTok Order ID');
            $table->string('platform', 30)->default('TIKTOK');

            // ─── Status ────────────────────────────────────────────────────
            $table->string('order_status', 40)->nullable()
                ->comment('UNPAID, ON_HOLD, AWAITING_SHIPMENT, PARTIALLY_SHIPPING, AWAITING_COLLECTION, IN_TRANSIT, DELIVERED, COMPLETED, CANCELLED');

            // ─── Buyer Info ────────────────────────────────────────────────
            $table->string('buyer_user_id', 50)->nullable();
            $table->string('buyer_name', 255)->nullable();
            $table->string('buyer_phone', 50)->nullable();
            $table->text('buyer_message')->nullable();

            // ─── Shipping ──────────────────────────────────────────────────
            $table->string('shipping_type', 30)->nullable()
                ->comment('TIKTOK, SELLER, TIKTOK_DIGITAL');
            $table->string('shipping_provider', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('shipping_address')->nullable()->comment('JSON full address');

            // ─── Amounts ───────────────────────────────────────────────────
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Total harga setelah diskon');
            $table->decimal('subtotal_amount', 15, 2)->default(0)->comment('Subtotal sebelum diskon');
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('seller_discount', 15, 2)->default(0);
            $table->decimal('platform_discount', 15, 2)->default(0);
            $table->string('currency', 5)->default('IDR');

            // ─── Payment ───────────────────────────────────────────────────
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_status', 30)->nullable();

            // ─── Flags ─────────────────────────────────────────────────────
            $table->boolean('is_cod')->default(false);
            $table->boolean('is_buyer_request_cancel')->default(false);
            $table->boolean('is_on_hold_order')->default(false);
            $table->boolean('is_replacement_order')->default(false);

            // ─── Timestamps dari TikTok ────────────────────────────────────
            $table->timestamp('paid_at')->nullable()->comment('Waktu pembayaran');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->integer('tiktok_create_time')->nullable()->comment('Unix timestamp dari TikTok');
            $table->integer('tiktok_update_time')->nullable()->comment('Unix timestamp dari TikTok');

            // ─── POS Integration (disiapkan, belum digunakan) ──────────────
            $table->boolean('is_synced_to_pos')->default(false)
                ->comment('Sudah dikirim ke database POS?');
            $table->timestamp('synced_to_pos_at')->nullable();
            $table->string('pos_order_id', 50)->nullable()
                ->comment('ID order di sistem POS setelah di-sync');

            // ─── Raw Data ──────────────────────────────────────────────────
            $table->json('raw_data')->nullable()->comment('JSON asli dari TikTok API untuk referensi');

            $table->timestamps();

            // ─── Foreign Keys ──────────────────────────────────────────────
            $table->foreign('account_id')->references('id')->on('account_shop_tiktok')->cascadeOnDelete();
            $table->foreign('channel_id')->references('id')->on('marketplace_channels')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            // ─── Indexes ───────────────────────────────────────────────────
            $table->unique(['order_id', 'platform'], 'unique_order_platform');
            $table->index('order_status');
            $table->index('account_id');
            $table->index('tiktok_create_time');
            $table->index('is_synced_to_pos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
