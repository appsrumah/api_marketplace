<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel khusus untuk akun toko Shopee.
     * Dipisahkan dari account_shop_tiktok dan channel_accounts agar lebih rapi.
     *
     * Kolom yang tersedia:
     *   channel_id              — FK ke marketplace_channels (Shopee id=2)
     *   user_id                 — FK ke users (siapa yang menghubungkan)
     *   seller_name             — Nama toko dari Shopee (shop_name di API)
     *   shop_id                 — Shop ID unik dari Shopee
     *   code                    — Auth code dari Shopee OAuth callback
     *   access_token            — Access token dari API
     *   access_token_expire_in  — Timestamp kadaluarsa access token
     *   refresh_token           — Refresh token dari API
     *   refresh_token_expire_in — Timestamp kadaluarsa refresh token
     *   id_outlet               — ID outlet di sistem POS (untuk push stok/order)
     *   telp_notif              — Nomor WA notifikasi order baru (via Wablas)
     *   status                  — active / expired / revoked
     *   token_obtained_at       — Waktu token pertama kali diperoleh
     *   last_sync_at            — Waktu terakhir sinkronisasi order
     *   last_update_stock       — Waktu terakhir update stok ke Shopee
     */
    public function up(): void
    {
        Schema::create('account_shop_shopee', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('channel_id')
                ->nullable()
                ->comment('FK ke marketplace_channels (Shopee id=2)');

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('User pemilik/pengelola akun ini');

            $table->string('seller_name')
                ->nullable()
                ->comment('Nama toko dari Shopee (shop_name di API)');

            $table->string('shop_id', 50)
                ->nullable()
                ->comment('Shop ID unik dari Shopee');

            $table->string('code', 200)
                ->nullable()
                ->comment('Auth code dari Shopee OAuth callback');

            $table->text('access_token')
                ->nullable()
                ->comment('Access token Shopee API');

            $table->timestamp('access_token_expire_in')
                ->nullable()
                ->comment('Timestamp kadaluarsa access token');

            $table->text('refresh_token')
                ->nullable()
                ->comment('Refresh token Shopee API');

            $table->timestamp('refresh_token_expire_in')
                ->nullable()
                ->comment('Timestamp kadaluarsa refresh token');

            $table->unsignedBigInteger('id_outlet')
                ->nullable()
                ->comment('ID outlet di sistem POS untuk push stok/order (kosong sementara)');

            $table->string('telp_notif')
                ->nullable()
                ->comment('Nomor WA notifikasi order baru — pisah koma untuk multi nomor');

            $table->enum('status', ['active', 'expired', 'revoked'])
                ->default('active');

            $table->timestamp('token_obtained_at')
                ->nullable()
                ->comment('Waktu token pertama kali diperoleh');

            $table->timestamp('last_sync_at')
                ->nullable()
                ->comment('Waktu terakhir sinkronisasi order');

            $table->timestamp('last_update_stock')
                ->nullable()
                ->comment('Waktu terakhir update stok ke Shopee');

            $table->timestamps();

            // Indeks
            $table->index('seller_name');
            $table->index('shop_id');
            $table->index('status');
            $table->index('user_id');

            // FK
            $table->foreign('channel_id')
                ->references('id')->on('marketplace_channels')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_shop_shopee');
    }
};
