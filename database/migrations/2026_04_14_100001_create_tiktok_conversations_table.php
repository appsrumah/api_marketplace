<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiktok_conversations', function (Blueprint $table) {
            $table->id();

            // FK ke account_shop_tiktok (multi-shop support)
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')
                  ->references('id')
                  ->on('account_shop_tiktok')
                  ->cascadeOnDelete();

            // Conversation ID unik dari TikTok
            $table->string('conversation_id', 100)->index();

            // Buyer info
            $table->string('buyer_user_id', 100)->nullable();
            $table->string('buyer_nickname')->nullable();
            $table->string('buyer_avatar_url', 500)->nullable();

            // Status percakapan
            $table->string('status', 30)->default('active')
                  ->comment('active, archived, closed');

            // Assigned agent CS (nullable = belum di-assign)
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->foreign('assigned_agent_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // Unread counter untuk agent
            $table->unsignedInteger('unread_count')->default(0);

            // Timestamp percakapan terakhir
            $table->timestamp('last_message_at')->nullable();

            // Timestamp dari TikTok (unix → datetime)
            $table->timestamp('tiktok_created_at')->nullable();

            $table->timestamps();

            // Unique: 1 conversation per account
            $table->unique(['account_id', 'conversation_id'], 'uq_account_conversation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiktok_conversations');
    }
};
