<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
      {
            Schema::create('wablas_bots', function (Blueprint $table) {
                  $table->id();

                  $table->string('name', 100)->comment('Nama label bot, e.g. "Bot Utama Oleh2"');

                  // ── Wablas Credentials ──────────────────────────────────
                  $table->string('server_url', 255)
                        ->default('https://pati.wablas.com')
                        ->comment('Base URL Wablas server');

                  $table->text('token')
                        ->comment('Token API dari Wablas');

                  $table->string('secret_key', 255)->nullable()
                        ->comment('Secret key tambahan (jika ada)');

                  // ── Nomor tujuan default ────────────────────────────────
                  $table->string('phone_notif', 500)
                        ->comment('Nomor HP penerima notifikasi (bisa koma-separated untuk multi)');

                  // ── Event config — event apa saja yang di-notifikasi ────
                  $table->boolean('notify_order_status')->default(true)
                        ->comment('Kirim notifikasi saat status order berubah');

                  $table->boolean('notify_new_message')->default(true)
                        ->comment('Kirim notifikasi saat ada pesan baru dari buyer');

                  $table->boolean('notify_cancellation')->default(false)
                        ->comment('Kirim notifikasi saat ada pembatalan');

                  $table->boolean('notify_return')->default(false)
                        ->comment('Kirim notifikasi saat ada retur');

                  $table->boolean('notify_product_change')->default(false)
                        ->comment('Kirim notifikasi saat produk berubah');

                  // ── Status & assignment ─────────────────────────────────
                  $table->boolean('is_active')->default(true);

                  $table->unsignedBigInteger('user_id')->nullable()
                        ->comment('User pemilik/admin bot ini');

                  $table->timestamps();

                  $table->index('is_active');
            });
      }

      public function down(): void
      {
            Schema::dropIfExists('wablas_bots');
      }
};
