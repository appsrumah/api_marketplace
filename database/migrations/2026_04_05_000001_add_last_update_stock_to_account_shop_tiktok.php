<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            // Kolom last_update_stock: waktu terakhir stok di-push ke TikTok
            if (! Schema::hasColumn('account_shop_tiktok', 'last_update_stock')) {
                $table->timestamp('last_update_stock')
                    ->nullable()
                    ->after('last_sync_at')
                    ->comment('Waktu terakhir stok berhasil di-update ke TikTok');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->dropColumn('last_update_stock');
        });
    }
};
