<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom role_id ke tabel users sebagai FK ke tabel roles.
     * Kolom enum `role` tetap dipertahankan untuk backward-compatibility
     * selama proses transisi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FK ke tabel roles (nullable agar existing users tidak error)
            $table->unsignedBigInteger('role_id')
                ->nullable()
                ->after('role')
                ->comment('FK ke tabel roles; null = belum di-assign ke role baru');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
