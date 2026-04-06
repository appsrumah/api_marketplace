<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel warehouses (gudang/outlet) — master data lokasi stok.
     * Menggantikan kolom id_outlet di account_shop_tiktok agar lebih terstruktur.
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable()->comment('User yang membuat');
            $table->string('name', 150)->comment('Nama gudang/outlet, misal: Gudang Utama Jakarta');
            $table->string('code', 30)->unique()->nullable()->comment('Kode pendek, misal: GDG-JKT-01');
            $table->string('pos_outlet_id', 50)->nullable()
                ->comment('ID outlet di sistem POS eksternal (misal: Kasir Pintar, Moka, dll.)');
            $table->text('address')->nullable()->comment('Alamat lengkap');
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('email', 150)->nullable()->comment('Email PIC gudang');
            $table->string('pic_name', 100)->nullable()->comment('Nama penanggung jawab');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Gudang default jika tidak ditentukan pada akun channel');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
