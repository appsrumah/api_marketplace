<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom id_outlet jika belum ada, atau ubah jadi nullable jika sudah ada
        if (!Schema::hasColumn('account_shop_tiktok', 'id_outlet')) {
            Schema::table('account_shop_tiktok', function (Blueprint $table) {
                $table->unsignedBigInteger('id_outlet')->nullable()->after('id');
            });
        } else {
            Schema::table('account_shop_tiktok', function (Blueprint $table) {
                $table->unsignedBigInteger('id_outlet')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->unsignedBigInteger('id_outlet')->nullable(false)->change();
        });
    }
};
