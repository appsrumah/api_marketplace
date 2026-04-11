<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->string('no_telp', 500)->nullable()
                ->after('shop_cipher')
                ->comment('Nomor HP penerima notifikasi Wablas (koma-separated, bisa banyak)');
        });
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->dropColumn('no_telp');
        });
    }
};
