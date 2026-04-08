<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel channel_accounts: generalisasi akun semua platform marketplace.
     * account_shop_tiktok tetap dipertahankan untuk TikTok (backward-compat),
     * platform baru (Shopee, Lazada, Tokopedia, Blibli, dst.) menggunakan tabel ini.
     */
    public function up(): void
    {
        Schema::create('channel_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id')->comment('FK ke marketplace_channels');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User pemilik/pengelola');
            $table->unsignedBigInteger('warehouse_id')->nullable()->comment('Warehouse/outlet default');

            // ─── Identitas Toko ────────────────────────────────────────────────
            $table->string('account_alias', 100)->nullable()
                ->comment('Label internal, misal: Toko Utama Shopee');
            $table->string('shop_id', 150)->nullable()->comment('ID toko dari platform');
            $table->string('shop_name', 255)->nullable()->comment('Nama toko di platform');
            $table->string('seller_name', 255)->nullable()->comment('Nama penjual/seller');
            $table->string('region', 10)->nullable()->comment('Kode negara, misal: ID');

            // ─── Kredensial OAuth/API ──────────────────────────────────────────
            $table->text('access_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->json('extra_credentials')->nullable()
                ->comment('Data tambahan: app_id, secret_key, partner_id, dll. (terenkripsi)');

            // ─── Status & Sync ─────────────────────────────────────────────────
            $table->enum('status', ['active', 'expired', 'revoked', 'disconnected'])
                ->default('active');
            $table->timestamp('token_obtained_at')->nullable();
            $table->timestamp('last_sync_at')->nullable()->comment('Terakhir sinkronisasi produk');
            $table->timestamp('last_update_stock')->nullable()->comment('Terakhir update stok');
            $table->timestamps();

            $table->foreign('channel_id')
                ->references('id')->on('marketplace_channels')
                ->restrictOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')->on('warehouses')
                ->nullOnDelete();

            $table->index(['channel_id', 'status']);
            $table->index('shop_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_accounts');
    }
};
