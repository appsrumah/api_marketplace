<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nama platform, misal: TikTok Shop');
            $table->string('slug', 50)->unique()->comment('Identifier unik lowercase: tiktok, shopee, tokopedia, dst.');
            $table->string('logo')->nullable()->comment('Path/URL logo platform');
            $table->string('color', 20)->nullable()->comment('Warna brand (hex), misal: #fe2c55');
            $table->string('bg_color', 20)->nullable()->comment('Warna background badge');
            $table->string('text_color', 20)->nullable()->comment('Warna teks badge');
            $table->string('api_base_url')->nullable()->comment('Base URL API resmi platform');
            $table->string('auth_type', 30)->nullable()
                ->comment('Mekanisme auth: oauth2, api_key, hmac, basic');
            $table->string('country_codes', 255)->nullable()
                ->comment('Kode negara yang didukung, comma-separated: ID,SG,MY');
            $table->boolean('is_active')->default(true)->comment('Aktif/tidak ditampilkan di UI');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Urutan tampil di UI');
            $table->text('notes')->nullable()->comment('Catatan internal');
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_channels');
    }
};
