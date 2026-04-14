<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('account_shop_shopee')->cascadeOnDelete();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();

            $table->string('order_sn', 64)->index();                // Shopee order_sn
            $table->string('order_status', 50)->default('UNPAID');

            // Buyer
            $table->string('buyer_user_id', 64)->nullable();
            $table->string('buyer_username', 100)->nullable();
            $table->string('buyer_name', 200)->nullable();
            $table->string('buyer_phone', 50)->nullable();
            $table->text('buyer_message')->nullable();

            // Shipping
            $table->string('shipping_carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->json('shipping_address')->nullable();

            // Financials
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('seller_discount', 15, 2)->default(0);
            $table->decimal('voucher_from_seller', 15, 2)->default(0);
            $table->decimal('voucher_from_shopee', 15, 2)->default(0);
            $table->decimal('coin_offset', 15, 2)->default(0);
            $table->string('currency', 10)->default('IDR');
            $table->string('payment_method', 100)->nullable();
            $table->boolean('is_cod')->default(false);

            // Timestamps from Shopee
            $table->unsignedBigInteger('create_time')->nullable();
            $table->unsignedBigInteger('update_time')->nullable();
            $table->unsignedBigInteger('pay_time')->nullable();
            $table->unsignedBigInteger('ship_by_date')->nullable();
            $table->unsignedInteger('days_to_ship')->nullable();

            // POS sync
            $table->boolean('is_synced_to_pos')->default(false);
            $table->timestamp('synced_to_pos_at')->nullable();
            $table->string('pos_order_id', 50)->nullable();

            // Raw data
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Unique: satu order_sn per akun
            $table->unique(['account_id', 'order_sn']);

            $table->foreign('channel_id')->references('id')->on('marketplace_channels')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_orders');
    }
};
