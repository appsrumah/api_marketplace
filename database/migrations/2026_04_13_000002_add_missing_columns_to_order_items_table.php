<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // tiktok_line_item_id was added manually via SQL — guard with hasColumn
            if (!Schema::hasColumn('order_items', 'tiktok_line_item_id')) {
                $table->string('tiktok_line_item_id', 50)->nullable()->after('tiktok_order_id');
            }

            if (!Schema::hasColumn('order_items', 'currency')) {
                $table->string('currency', 10)->default('IDR')->after('subtotal');
            }

            if (!Schema::hasColumn('order_items', 'item_status')) {
                $table->string('item_status', 50)->nullable()->after('currency');
            }

            if (!Schema::hasColumn('order_items', 'is_gift')) {
                $table->boolean('is_gift')->default(false)->after('item_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('order_items', 'tiktok_line_item_id') ? 'tiktok_line_item_id' : null,
                Schema::hasColumn('order_items', 'currency')            ? 'currency'            : null,
                Schema::hasColumn('order_items', 'item_status')         ? 'item_status'         : null,
                Schema::hasColumn('order_items', 'is_gift')             ? 'is_gift'             : null,
            ]));
        });
    }
};
