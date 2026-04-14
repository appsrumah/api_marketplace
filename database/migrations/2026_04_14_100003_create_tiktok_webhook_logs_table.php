<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_webhook_logs', function (Blueprint $table) {
            $table->id();

            // Tipe event webhook (13 = conversation, 14 = message, dll)
            $table->unsignedSmallInteger('event_type')->nullable();
            $table->string('event_name', 50)->nullable()
                ->comment('NEW_CONVERSATION, NEW_MESSAGE, dll');

            // Account yang terkait (nullable jika belum bisa di-resolve dari payload)
            $table->unsignedBigInteger('account_id')->nullable();

            // Raw payload untuk debugging
            $table->json('raw_payload')->nullable();

            // Status processing
            $table->string('process_status', 20)->default('pending')
                ->comment('pending, processing, completed, failed');
            $table->text('process_error')->nullable();

            // Timestamp saat selesai diproses
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Index untuk query log dan cleanup
            $table->index('event_type');
            $table->index('process_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_webhook_logs');
    }
};
