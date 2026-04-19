<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produk_saya', function (Blueprint $table) {
            $table->integer('last_pushed_stock')->nullable()->after('quantity');
            $table->timestamp('last_pushed_at')->nullable()->after('last_pushed_stock');
        });

        // Backfill existing rows: set last_pushed_stock = quantity, last_pushed_at = now
        try {
            DB::statement("UPDATE produk_saya SET last_pushed_stock = quantity, last_pushed_at = NOW()");
        } catch (\Throwable $e) {
            // If the DB driver doesn't support NOW() or statement fails, ignore — safe fallback
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produk_saya', function (Blueprint $table) {
            $table->dropColumn(['last_pushed_stock', 'last_pushed_at']);
        });
    }
};
