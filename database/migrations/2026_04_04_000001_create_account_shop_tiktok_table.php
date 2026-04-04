<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_shop_tiktok', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->timestamp('access_token_expire_in')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('refresh_token_expire_in')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('seller_base_region', 10)->nullable();
            $table->string('shop_cipher')->nullable();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->timestamp('token_obtained_at')->nullable();
            $table->timestamps();

            $table->index('seller_name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_shop_tiktok');
    }
};
