<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->string('shop_id', 50)->nullable()->after('shop_cipher');
            $table->string('shop_name')->nullable()->after('shop_id');
            $table->timestamp('last_sync_at')->nullable()->after('token_obtained_at');
        });
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->dropColumn(['shop_id', 'shop_name', 'last_sync_at']);
        });
    }
};
