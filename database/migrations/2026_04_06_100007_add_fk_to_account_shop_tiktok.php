<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah FK channel_id, user_id, warehouse_id ke account_shop_tiktok.
     * - channel_id : FK ke marketplace_channels (channel = TikTok)
     * - user_id    : FK ke users (siapa yang menghubungkan akun ini)
     * - warehouse_id: FK ke warehouses (warehouse/outlet default untuk akun ini)
     */
    public function up(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')
                ->nullable()
                ->after('id')
                ->comment('FK ke marketplace_channels');

            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->after('channel_id')
                ->comment('User pemilik/pengelola akun ini');

            $table->unsignedBigInteger('warehouse_id')
                ->nullable()
                ->after('user_id')
                ->comment('Warehouse/outlet default; menggantikan id_outlet');

            $table->foreign('channel_id')
                ->references('id')->on('marketplace_channels')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')->on('warehouses')
                ->nullOnDelete();

            $table->index('user_id');
            $table->index('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('account_shop_tiktok', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn(['channel_id', 'user_id', 'warehouse_id']);
        });
    }
};
