<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah channel_id ke produk_saya agar setiap produk
     * terhubung langsung ke marketplace_channels (query lebih efisien).
     */
    public function up(): void
    {
        Schema::table('produk_saya', function (Blueprint $table) {
            $table->unsignedBigInteger('channel_id')
                ->nullable()
                ->after('account_id')
                ->comment('FK ke marketplace_channels; denormalized dari account.channel_id');

            $table->foreign('channel_id')
                ->references('id')->on('marketplace_channels')
                ->nullOnDelete();

            $table->index('channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('produk_saya', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropIndex(['channel_id']);
            $table->dropColumn('channel_id');
        });
    }
};
