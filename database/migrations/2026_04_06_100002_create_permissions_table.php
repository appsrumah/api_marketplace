<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()
                ->comment('Slug permission, misal: users.create, products.sync');
            $table->string('label', 150)->comment('Nama tampilan, misal: Tambah Pengguna');
            $table->string('group', 50)->comment('Kelompok: users, products, orders, stock, channels, reports, settings, warehouses');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
