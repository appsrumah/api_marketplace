<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('Slug unik, misal: super_admin, manager');
            $table->string('label', 100)->comment('Nama tampilan, misal: Super Admin');
            $table->text('description')->nullable()->comment('Deskripsi hak akses role ini');
            $table->unsignedTinyInteger('level')->default(10)
                ->comment('Hierarki: nilai lebih tinggi = akses lebih besar. super_admin=100, admin=80, dst.');
            $table->boolean('is_system')->default(false)
                ->comment('Role sistem: tidak bisa dihapus/diubah namanya');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
