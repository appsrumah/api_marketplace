<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom telp_notif ke account_shop_tiktok.
     * Digunakan untuk menyimpan nomor WhatsApp yang menerima notifikasi order baru
     * (bisa lebih dari satu, dipisah koma).
     */
    public function up(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            if (! Schema::hasColumn('account_shop_tiktok', 'telp_notif')) {
                $table->string('telp_notif')->nullable()
                    ->after('id_outlet')
                    ->comment('Nomor WA notifikasi order baru (pisah koma untuk multi nomor)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->dropColumn('telp_notif');
        });
    }
};
