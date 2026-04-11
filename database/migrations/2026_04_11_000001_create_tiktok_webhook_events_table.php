<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
      {
            Schema::create('tiktok_webhook_events', function (Blueprint $table) {
                  $table->id();

                  // ── Identitas akun ──────────────────────────────────────
                  $table->unsignedBigInteger('account_id')->nullable()
                        ->comment('FK ke account_shop_tiktok');

                  $table->string('shop_id', 50)->nullable()
                        ->comment('TikTok shop_id pengirim event');

                  // ── Event info ──────────────────────────────────────────
                  $table->unsignedTinyInteger('type')
                        ->comment('1=ORDER_STATUS_CHANGE, 2=NEW_MESSAGE, 3=NEW_MESSAGE_LISTENER, 4=PACKAGE_UPDATE, 5=PRODUCT_STATUS_CHANGE, 6=CANCELLATION_STATUS_CHANGE, 7=RETURN_STATUS_CHANGE, 8=REVERSE_STATUS_UPDATE, 9=SELLER_DEAUTHORIZATION, 10=UPCOMING_AUTHORIZATION_EXPIRATION, 99=OTHER');

                  $table->string('event_type', 80)
                        ->comment('Nama event asli dari TikTok, e.g. ORDER_STATUS_CHANGE');

                  $table->unsignedInteger('tiktok_timestamp')->nullable()
                        ->comment('Unix timestamp event dari TikTok');

                  // ── Payload — data penting dari event ───────────────────
                  $table->string('order_id', 80)->nullable()
                        ->comment('order_id jika event terkait pesanan');

                  $table->string('order_status', 40)->nullable()
                        ->comment('Status order baru (AWAITING_SHIPMENT, etc.)');

                  $table->string('conversation_id', 80)->nullable()
                        ->comment('conversation_id jika event terkait pesan');

                  $table->string('product_id', 80)->nullable()
                        ->comment('product_id jika event terkait produk');

                  $table->json('payload')->nullable()
                        ->comment('Raw JSON body dari TikTok (full)');

                  // ── Processing status ───────────────────────────────────
                  $table->enum('status', ['received', 'processing', 'processed', 'failed', 'ignored'])
                        ->default('received');

                  $table->boolean('notified')->default(false)
                        ->comment('Sudah dikirim notifikasi Wablas?');

                  $table->text('error_message')->nullable();

                  $table->timestamps();

                  // ── Indexes ─────────────────────────────────────────────
                  $table->index('account_id');
                  $table->index('event_type');
                  $table->index('order_id');
                  $table->index('status');
                  $table->index('created_at');
                  $table->index(['shop_id', 'event_type']);
            });
      }

      public function down(): void
      {
            Schema::dropIfExists('tiktok_webhook_events');
      }
};
