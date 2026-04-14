<?php

namespace Tests\Unit;

use App\Jobs\ProcessTikTokWebhookJob;
use App\Models\AccountShopTiktok;
use App\Models\TikTokConversation;
use App\Models\TikTokMessage;
use App\Models\TikTokWebhookLog;
use App\Services\TikTokCustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TikTokWebhookTest extends TestCase
{
    use RefreshDatabase;

    /* ===================================================================
     *  HELPER: Buat akun TikTok shop untuk testing
     * =================================================================== */

    private function createTestAccount(array $overrides = []): AccountShopTiktok
    {
        return AccountShopTiktok::create(array_merge([
            'shop_id'                 => 'test_shop_123',
            'shop_name'               => 'Toko Test',
            'seller_name'             => 'Seller Test',
            'shop_cipher'             => 'cipher_test',
            'access_token'            => 'test_access_token',
            'refresh_token'           => 'test_refresh_token',
            'access_token_expire_in'  => now()->addHours(4),
            'refresh_token_expire_in' => now()->addDays(30),
            'status'                  => 'active',
        ], $overrides));
    }

    /* ===================================================================
     *  TEST: Verifikasi Signature Webhook
     * =================================================================== */

    /** @test */
    public function it_verifies_valid_webhook_signature(): void
    {
        $secret  = 'test_webhook_secret_2026';
        $rawBody = '{"type":14,"shop_id":"test_shop_123","data":{}}';

        config(['tiktok_cs.webhook_secret' => $secret]);

        $expectedSignature = hash_hmac('sha256', $rawBody, $secret);

        $this->assertTrue(
            TikTokCustomerService::verifySignature($rawBody, $expectedSignature)
        );
    }

    /** @test */
    public function it_rejects_invalid_webhook_signature(): void
    {
        $secret  = 'test_webhook_secret_2026';
        $rawBody = '{"type":14,"shop_id":"test_shop_123","data":{}}';

        config(['tiktok_cs.webhook_secret' => $secret]);

        $this->assertFalse(
            TikTokCustomerService::verifySignature($rawBody, 'invalid_signature')
        );
    }

    /** @test */
    public function it_allows_request_when_secret_not_configured(): void
    {
        config(['tiktok_cs.webhook_secret' => '']);

        $this->assertTrue(
            TikTokCustomerService::verifySignature('any_body', 'any_signature')
        );
    }

    /* ===================================================================
     *  TEST: Webhook Endpoint — Response & Queue Dispatch
     * =================================================================== */

    /** @test */
    public function webhook_endpoint_returns_200_and_dispatches_job(): void
    {
        Queue::fake();
        config(['tiktok_cs.verify_signature' => false]);

        $payload = [
            'type'    => 14,
            'shop_id' => 'test_shop_123',
            'data'    => [
                'conversation_id' => 'conv_001',
                'message_id'      => 'msg_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_001'],
                'content'         => ['type' => 'text', 'text' => ['text' => 'Halo!']],
                'create_time'     => time(),
            ],
        ];

        $response = $this->postJson('/webhooks/tiktok/customer-service', $payload);

        $response->assertStatus(200)
            ->assertJson(['code' => 0, 'message' => 'OK']);

        // Pastikan raw payload disimpan
        $this->assertDatabaseHas('tiktok_webhook_logs', [
            'event_type' => 14,
            'event_name' => 'NEW_MESSAGE',
        ]);

        // Pastikan job di-dispatch
        Queue::assertPushed(ProcessTikTokWebhookJob::class);
    }

    /** @test */
    public function webhook_with_invalid_signature_returns_200_but_no_job(): void
    {
        Queue::fake();
        config([
            'tiktok_cs.verify_signature' => true,
            'tiktok_cs.webhook_secret'   => 'my_secret',
        ]);

        $payload = ['type' => 14, 'data' => []];

        $response = $this->postJson(
            '/webhooks/tiktok/customer-service',
            $payload,
            ['Authorization' => 'wrong_signature']
        );

        // Tetap 200 OK (TikTok requirement)
        $response->assertStatus(200);

        // Tapi tidak ada job yang di-dispatch
        Queue::assertNothingPushed();
    }

    /* ===================================================================
     *  TEST: Process NEW_CONVERSATION (type 13)
     * =================================================================== */

    /** @test */
    public function it_processes_new_conversation_event(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        $payload = [
            'type'    => 13,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_test_001',
                'buyer_uid'       => 'buyer_uid_001',
                'buyer_nickname'  => 'Pembeli Test',
                'buyer_avatar'    => 'https://example.com/avatar.jpg',
                'create_time'     => 1712000000,
            ],
        ];

        $log = TikTokWebhookLog::create([
            'event_type'     => 13,
            'event_name'     => 'NEW_CONVERSATION',
            'raw_payload'    => $payload,
            'process_status' => TikTokWebhookLog::STATUS_PENDING,
        ]);

        $service = app(TikTokCustomerService::class);
        $service->handleWebhook($payload, $log);

        // Conversation tersimpan di DB
        $this->assertDatabaseHas('tiktok_conversations', [
            'account_id'      => $account->id,
            'conversation_id' => 'conv_test_001',
            'buyer_user_id'   => 'buyer_uid_001',
            'buyer_nickname'  => 'Pembeli Test',
            'status'          => 'active',
        ]);

        // Log status updated
        $log->refresh();
        $this->assertEquals(TikTokWebhookLog::STATUS_COMPLETED, $log->process_status);
        $this->assertNotNull($log->processed_at);

        // Event dispatched
        Event::assertDispatched(\App\Events\NewConversationReceived::class);
    }

    /* ===================================================================
     *  TEST: Process NEW_MESSAGE — Text
     * =================================================================== */

    /** @test */
    public function it_processes_new_text_message(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        // Pre-create conversation
        $conversation = TikTokConversation::create([
            'account_id'      => $account->id,
            'conversation_id' => 'conv_msg_001',
            'status'          => 'active',
        ]);

        $payload = [
            'type'    => 14,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_msg_001',
                'message_id'      => 'msg_text_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_001'],
                'content'         => [
                    'type' => 'text',
                    'text' => ['text' => 'Halo, apakah barang masih ready?'],
                ],
                'create_time'     => 1712000100,
            ],
        ];

        $log = TikTokWebhookLog::create([
            'event_type'     => 14,
            'event_name'     => 'NEW_MESSAGE',
            'raw_payload'    => $payload,
            'process_status' => TikTokWebhookLog::STATUS_PENDING,
        ]);

        $service = app(TikTokCustomerService::class);
        $service->handleWebhook($payload, $log);

        // Message tersimpan
        $this->assertDatabaseHas('tiktok_messages', [
            'conversation_id' => $conversation->id,
            'message_id'      => 'msg_text_001',
            'sender_type'     => 'BUYER',
            'content_type'    => 'text',
            'content'         => 'Halo, apakah barang masih ready?',
        ]);

        // Log completed
        $log->refresh();
        $this->assertEquals(TikTokWebhookLog::STATUS_COMPLETED, $log->process_status);

        Event::assertDispatched(\App\Events\NewMessageReceived::class);
    }

    /* ===================================================================
     *  TEST: Process NEW_MESSAGE — Image
     * =================================================================== */

    /** @test */
    public function it_processes_new_image_message(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        $conversation = TikTokConversation::create([
            'account_id'      => $account->id,
            'conversation_id' => 'conv_img_001',
            'status'          => 'active',
        ]);

        $imageUrl = 'https://p16-oec-sg.ibyteimg.com/tos-alisg-i-xxx/image.jpg';

        $payload = [
            'type'    => 14,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_img_001',
                'message_id'      => 'msg_img_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_002'],
                'content'         => [
                    'type'  => 'image',
                    'image' => ['url' => $imageUrl],
                ],
                'create_time'     => 1712000200,
            ],
        ];

        $log = TikTokWebhookLog::create([
            'event_type'     => 14,
            'event_name'     => 'NEW_MESSAGE',
            'raw_payload'    => $payload,
            'process_status' => TikTokWebhookLog::STATUS_PENDING,
        ]);

        $service = app(TikTokCustomerService::class);
        $service->handleWebhook($payload, $log);

        $this->assertDatabaseHas('tiktok_messages', [
            'conversation_id' => $conversation->id,
            'message_id'      => 'msg_img_001',
            'sender_type'     => 'BUYER',
            'content_type'    => 'image',
            'content'         => $imageUrl,
        ]);
    }

    /* ===================================================================
     *  TEST: Process NEW_MESSAGE — Product Card
     * =================================================================== */

    /** @test */
    public function it_processes_new_product_card_message(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        $conversation = TikTokConversation::create([
            'account_id'      => $account->id,
            'conversation_id' => 'conv_prod_001',
            'status'          => 'active',
        ]);

        $productCardData = [
            'product_id'    => 'prod_123',
            'product_name'  => 'Kaos Polos XL',
            'product_image' => 'https://example.com/product.jpg',
            'price'         => '75000',
        ];

        $payload = [
            'type'    => 14,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_prod_001',
                'message_id'      => 'msg_prod_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_003'],
                'content'         => [
                    'type'         => 'product_card',
                    'product_card' => $productCardData,
                ],
                'create_time'     => 1712000300,
            ],
        ];

        $log = TikTokWebhookLog::create([
            'event_type'     => 14,
            'event_name'     => 'NEW_MESSAGE',
            'raw_payload'    => $payload,
            'process_status' => TikTokWebhookLog::STATUS_PENDING,
        ]);

        $service = app(TikTokCustomerService::class);
        $service->handleWebhook($payload, $log);

        $this->assertDatabaseHas('tiktok_messages', [
            'conversation_id' => $conversation->id,
            'message_id'      => 'msg_prod_001',
            'sender_type'     => 'BUYER',
            'content_type'    => 'product_card',
        ]);

        // Verify product card data tersimpan sebagai JSON
        $message = TikTokMessage::where('message_id', 'msg_prod_001')->first();
        $this->assertNotNull($message);
        $decoded = json_decode($message->content, true);
        $this->assertEquals('prod_123', $decoded['product_id']);
        $this->assertEquals('Kaos Polos XL', $decoded['product_name']);
    }

    /* ===================================================================
     *  TEST: Idempotent — Webhook duplikat tidak membuat record baru
     * =================================================================== */

    /** @test */
    public function it_handles_duplicate_messages_idempotently(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        TikTokConversation::create([
            'account_id'      => $account->id,
            'conversation_id' => 'conv_dup_001',
            'status'          => 'active',
        ]);

        $payload = [
            'type'    => 14,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_dup_001',
                'message_id'      => 'msg_dup_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_dup'],
                'content'         => ['type' => 'text', 'text' => ['text' => 'Duplikat']],
                'create_time'     => 1712000400,
            ],
        ];

        $service = app(TikTokCustomerService::class);

        // Proses 2x
        $log1 = TikTokWebhookLog::create([
            'event_type' => 14,
            'event_name' => 'NEW_MESSAGE',
            'raw_payload' => $payload,
            'process_status' => 'pending',
        ]);
        $log2 = TikTokWebhookLog::create([
            'event_type' => 14,
            'event_name' => 'NEW_MESSAGE',
            'raw_payload' => $payload,
            'process_status' => 'pending',
        ]);

        $service->handleWebhook($payload, $log1);
        $service->handleWebhook($payload, $log2);

        // Hanya 1 message di DB
        $this->assertEquals(1, TikTokMessage::where('message_id', 'msg_dup_001')->count());
    }

    /* ===================================================================
     *  TEST: Auto-create conversation saat pesan masuk tanpa type 13
     * =================================================================== */

    /** @test */
    public function it_auto_creates_conversation_on_new_message(): void
    {
        Event::fake();

        $account = $this->createTestAccount();

        // Tidak ada conversation sebelumnya
        $this->assertEquals(0, TikTokConversation::count());

        $payload = [
            'type'    => 14,
            'shop_id' => $account->shop_id,
            'data'    => [
                'conversation_id' => 'conv_auto_001',
                'message_id'      => 'msg_auto_001',
                'sender'          => ['type' => 'BUYER', 'uid' => 'buyer_auto'],
                'content'         => ['type' => 'text', 'text' => ['text' => 'Auto-create test']],
                'create_time'     => 1712000500,
            ],
        ];

        $log = TikTokWebhookLog::create([
            'event_type' => 14,
            'event_name' => 'NEW_MESSAGE',
            'raw_payload' => $payload,
            'process_status' => 'pending',
        ]);

        $service = app(TikTokCustomerService::class);
        $service->handleWebhook($payload, $log);

        // Conversation auto-created
        $this->assertEquals(1, TikTokConversation::count());
        $this->assertDatabaseHas('tiktok_conversations', [
            'account_id'      => $account->id,
            'conversation_id' => 'conv_auto_001',
        ]);
    }
}
