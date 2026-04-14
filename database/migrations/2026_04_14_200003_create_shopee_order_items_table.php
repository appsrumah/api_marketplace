<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopee_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopee_order_id')->constrained('shopee_orders')->cascadeOnDelete();

            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name', 300)->nullable();
            $table->string('item_sku', 100)->nullable();

            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model_name', 300)->nullable();
            $table->string('model_sku', 100)->nullable();

            $table->decimal('model_original_price', 15, 2)->default(0);
            $table->decimal('model_discounted_price', 15, 2)->default(0);
            $table->unsignedInteger('quantity_purchased')->default(1);

            $table->string('image_url', 500)->nullable();
            $table->decimal('weight', 10, 3)->default(0);
            $table->boolean('is_wholesale')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopee_order_items');
    }
};
