<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel system_settings: konfigurasi global aplikasi berbasis key-value.
     * Menggantikan penggunaan hard-coded config di .env untuk setting yang perlu
     * diubah dari UI tanpa deploy ulang.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()
                ->comment('Kunci unik, misal: app.name, sync.auto_interval, notification.email');
            $table->text('value')->nullable()->comment('Nilai setting (disimpan sebagai string/JSON)');
            $table->string('type', 20)->default('string')
                ->comment('Tipe data: string, boolean, integer, float, json, array');
            $table->string('group', 50)->default('general')
                ->comment('Kelompok: general, sync, notification, security, api, appearance');
            $table->string('label', 200)->nullable()->comment('Label tampilan di UI settings');
            $table->text('description')->nullable()->comment('Penjelasan fungsi setting ini');
            $table->boolean('is_public')->default(false)
                ->comment('true = bisa dibaca tanpa login (misal: nama aplikasi)');
            $table->boolean('is_encrypted')->default(false)
                ->comment('true = nilai dienkripsi sebelum disimpan');
            $table->timestamps();

            $table->index('group');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
