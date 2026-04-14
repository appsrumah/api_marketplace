<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_messages', function (Blueprint $table) {
            $table->id();

            // FK ke tiktok_conversations
            $table->unsignedBigInteger('conversation_id');
            $table->foreign('conversation_id')
                ->references('id')
                ->on('tiktok_conversations')
                ->cascadeOnDelete();

            // Message ID unik dari TikTok
            $table->string('message_id', 100)->index();

            // Siapa yang mengirim
            $table->string('sender_type', 30)
                ->comment('BUYER, CUSTOMER_SERVICE, SHOP, SYSTEM, ROBOT');
            $table->string('sender_id', 100)->nullable();

            // Tipe konten & isi pesan
            $table->string('content_type', 30)->default('text')
                ->comment('text, image, video, product_card, order_card, emoji, sticker, file');
            $table->text('content')->nullable()
                ->comment('Text pesan, atau JSON berisi URL/metadata untuk non-text');

            // Metadata tambahan (full payload dari TikTok)
            $table->json('metadata')->nullable();

            // Sudah dibaca oleh agent?
            $table->boolean('is_read')->default(false);

            // Timestamp pesan dari TikTok (unix → datetime)
            $table->timestamp('tiktok_created_at')->nullable();

            $table->timestamps();

            // Unique: 1 message_id per conversation
            $table->unique(['conversation_id', 'message_id'], 'uq_conversation_message');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_messages');
    }
};
