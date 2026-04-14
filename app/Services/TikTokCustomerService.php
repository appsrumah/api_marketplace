<?php

namespace App\Services;

use App\Events\NewConversationReceived;
use App\Events\NewMessageReceived;
use App\Models\AccountShopTiktok;
use App\Models\TikTokConversation;
use App\Models\TikTokMessage;
use App\Models\TikTokWebhookLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TikTokCustomerService — Business logic untuk Customer Service webhook & API.
 *
 * Menggunakan TikTok Customer Service API v202309:
 * - GET  /customer_service/202309/conversations
 * - GET  /customer_service/202309/conversations/{conversation_id}/messages
 * - POST /customer_service/202309/conversations/{conversation_id}/messages
 *
 * Referensi: https://partner.tiktokshop.com/docv2/page/6507ead7b99d5302be949ba9
 */
class TikTokCustomerService
{
    private string $appKey;
    private string $appSecret;
    private string $apiBase;
    private int    $timeout;
    private int    $connectTimeout;

    public function __construct(
        private TiktokApiService $tiktokApiService
    ) {
        $this->appKey         = config('tiktok_cs.app_key') ?: config('services.tiktok.app_key', '');
        $this->appSecret      = config('tiktok_cs.app_secret') ?: config('services.tiktok.app_secret', '');
        $this->apiBase        = config('tiktok_cs.api_base') ?: config('services.tiktok.api_base', '');
        $this->timeout        = (int) config('tiktok_cs.api_timeout', 15);
        $this->connectTimeout = (int) config('tiktok_cs.api_connect_timeout', 5);
    }

    /* ===================================================================
     *  WEBHOOK HANDLER — Entry point dari ProcessTikTokWebhookJob
     * =================================================================== */

    /**
     * Proses payload webhook dan dispatch ke handler sesuai event type.
     *
     * @param  array            $payload  Decoded JSON dari TikTok webhook
     * @param  TikTokWebhookLog $log      Log entry untuk tracking
     * @return void
     */
    public function handleWebhook(array $payload, TikTokWebhookLog $log): void
    {
        $type = (int) ($payload['type'] ?? 0);

        try {
            $log->update(['process_status' => TikTokWebhookLog::STATUS_PROCESSING]);

            match ($type) {
                13 => $this->processNewConversation($payload, $log),
                14 => $this->processNewMessage($payload, $log),
                default => Log::warning('TikTokCS: Unhandled webhook type', [
                    'type'   => $type,
                    'log_id' => $log->id,
                ]),
            };

            $log->markCompleted();
        } catch (\Throwable $e) {
            Log::error('TikTokCS: Webhook processing failed', [
                'type'   => $type,
                'log_id' => $log->id,
                'error'  => $e->getMessage(),
            ]);
            $log->markFailed($e->getMessage());
        }
    }

    /* ===================================================================
     *  TYPE 13 — NEW_CONVERSATION (agent join/leave, conversation created)
     * =================================================================== */

    /**
     * Proses event NEW_CONVERSATION (type 13).
     *
     * Payload structure (dari TikTok):
     * {
     *   "type": 13,
     *   "shop_id": "xxx",
     *   "data": {
     *     "conversation_id": "xxx",
     *     "buyer_uid": "xxx",
     *     "buyer_nickname": "xxx",
     *     "buyer_avatar": "https://...",
     *     "create_time": 1234567890
     *   }
     * }
     */
    public function processNewConversation(array $payload, ?TikTokWebhookLog $log = null): ?TikTokConversation
    {
        $data   = $payload['data'] ?? $payload;
        $shopId = $payload['shop_id'] ?? ($data['shop_id'] ?? null);

        $conversationId = $data['conversation_id'] ?? null;
        if (!$conversationId) {
            Log::warning('TikTokCS: conversation_id missing in type 13 payload');
            return null;
        }

        // Resolve akun berdasar shop_id
        $account = $this->resolveAccount($shopId);
        if (!$account) {
            Log::warning('TikTokCS: Cannot resolve account for shop_id', ['shop_id' => $shopId]);
            return null;
        }

        // Update log dengan account_id
        $log?->update(['account_id' => $account->id]);

        // Upsert conversation
        $conversation = TikTokConversation::updateOrCreate(
            [
                'account_id'      => $account->id,
                'conversation_id' => $conversationId,
            ],
            [
                'buyer_user_id'    => $data['buyer_uid'] ?? $data['buyer_user_id'] ?? null,
                'buyer_nickname'   => $data['buyer_nickname'] ?? null,
                'buyer_avatar_url' => $data['buyer_avatar'] ?? $data['buyer_avatar_url'] ?? null,
                'status'           => TikTokConversation::STATUS_ACTIVE,
                'tiktok_created_at' => $this->toCarbonFromTimestamp($data['create_time'] ?? null),
            ]
        );

        // Fire event (broadcast + listener)
        NewConversationReceived::dispatch($conversation, $payload);

        Log::info('TikTokCS: New conversation processed', [
            'conversation_id' => $conversationId,
            'account_id'      => $account->id,
        ]);

        return $conversation;
    }

    /* ===================================================================
     *  TYPE 14 — NEW_MESSAGE (pesan baru dari buyer/agent/system)
     * =================================================================== */

    /**
     * Proses event NEW_MESSAGE (type 14).
     *
     * Payload structure:
     * {
     *   "type": 14,
     *   "shop_id": "xxx",
     *   "data": {
     *     "conversation_id": "xxx",
     *     "message_id": "xxx",
     *     "sender": { "type": "BUYER", "uid": "xxx" },
     *     "content": { "type": "text", "text": { "text": "Halo..." } },
     *     "create_time": 1234567890
     *   }
     * }
     */
    public function processNewMessage(array $payload, ?TikTokWebhookLog $log = null): ?TikTokMessage
    {
        $data   = $payload['data'] ?? $payload;
        $shopId = $payload['shop_id'] ?? ($data['shop_id'] ?? null);

        $conversationId = $data['conversation_id'] ?? null;
        $messageId      = $data['message_id'] ?? null;
        if (!$conversationId || !$messageId) {
            Log::warning('TikTokCS: conversation_id or message_id missing in type 14 payload');
            return null;
        }

        // Resolve akun
        $account = $this->resolveAccount($shopId);
        if (!$account) {
            Log::warning('TikTokCS: Cannot resolve account for shop_id', ['shop_id' => $shopId]);
            return null;
        }

        $log?->update(['account_id' => $account->id]);

        // Pastikan conversation sudah ada (auto-create jika belum)
        $conversation = TikTokConversation::firstOrCreate(
            [
                'account_id'      => $account->id,
                'conversation_id' => $conversationId,
            ],
            [
                'status'           => TikTokConversation::STATUS_ACTIVE,
                'tiktok_created_at' => now(),
            ]
        );

        // Parse sender
        $sender     = $data['sender'] ?? [];
        $senderType = strtoupper($sender['type'] ?? $data['sender_type'] ?? 'BUYER');
        $senderId   = $sender['uid'] ?? $sender['id'] ?? $data['sender_id'] ?? null;

        // Parse content
        $contentData = $data['content'] ?? [];
        $contentType = $contentData['type'] ?? $data['content_type'] ?? 'text';
        $contentText = $this->extractMessageContent($contentData, $contentType);

        // Upsert message (idempotent: message_id + conversation unik)
        $message = TikTokMessage::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'message_id'      => $messageId,
            ],
            [
                'sender_type'      => $senderType,
                'sender_id'        => $senderId,
                'content_type'     => $contentType,
                'content'          => $contentText,
                'metadata'         => $contentData,
                'tiktok_created_at' => $this->toCarbonFromTimestamp($data['create_time'] ?? null),
            ]
        );

        // Update last_message_at di conversation
        $conversation->update(['last_message_at' => $message->tiktok_created_at ?? now()]);

        // Fire event
        NewMessageReceived::dispatch($message, $conversation, $payload);

        Log::info('TikTokCS: New message processed', [
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'sender'          => $senderType,
            'content_type'    => $contentType,
        ]);

        return $message;
    }

    /* ===================================================================
     *  SYNC CONVERSATION — Pull data percakapan dari TikTok API
     * =================================================================== */

    /**
     * Sinkronisasi satu percakapan dari TikTok API.
     *
     * @param  AccountShopTiktok $account
     * @param  string            $conversationId  TikTok conversation ID
     * @return TikTokConversation|null
     */
    public function syncConversation(AccountShopTiktok $account, string $conversationId): ?TikTokConversation
    {
        $accessToken = $account->access_token;
        $shopCipher  = $account->shop_cipher;

        // Fetch messages via API
        $path      = "/customer_service/202309/conversations/{$conversationId}/messages";
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
            'page_size'   => 50,
        ];

        $sign = $this->tiktokApiService->buildSign($path, $queryParams);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders([
                'Content-Type'       => 'application/json',
                'x-tts-access-token' => $accessToken,
            ])
            ->get($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTokCS: syncConversation failed', [
                'conversation_id' => $conversationId,
                'response'        => $data,
            ]);
            return null;
        }

        $messages = $data['data']['messages'] ?? [];

        // Upsert conversation
        $conversation = TikTokConversation::firstOrCreate(
            [
                'account_id'      => $account->id,
                'conversation_id' => $conversationId,
            ],
            [
                'status'           => TikTokConversation::STATUS_ACTIVE,
                'tiktok_created_at' => now(),
            ]
        );

        // Upsert messages
        foreach ($messages as $msg) {
            $senderType = strtoupper($msg['sender']['type'] ?? 'BUYER');
            $contentData = $msg['content'] ?? [];
            $contentType = $contentData['type'] ?? 'text';

            TikTokMessage::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'message_id'      => $msg['message_id'] ?? $msg['id'] ?? null,
                ],
                [
                    'sender_type'       => $senderType,
                    'sender_id'         => $msg['sender']['uid'] ?? null,
                    'content_type'      => $contentType,
                    'content'           => $this->extractMessageContent($contentData, $contentType),
                    'metadata'          => $contentData,
                    'tiktok_created_at' => $this->toCarbonFromTimestamp($msg['create_time'] ?? null),
                ]
            );
        }

        // Update last_message_at
        $latestMsg = $conversation->messages()->latest('tiktok_created_at')->first();
        if ($latestMsg) {
            $conversation->update(['last_message_at' => $latestMsg->tiktok_created_at]);
        }

        Log::info('TikTokCS: Conversation synced', [
            'conversation_id' => $conversationId,
            'messages_count'  => count($messages),
        ]);

        return $conversation;
    }

    /**
     * List semua percakapan dari TikTok API dan sync ke lokal.
     *
     * @return array{synced: int, pages: int}
     */
    public function syncAllConversations(AccountShopTiktok $account): array
    {
        $accessToken = $account->access_token;
        $shopCipher  = $account->shop_cipher;
        $pageSize    = config('tiktok_cs.sync_page_size', 20);
        $maxPages    = config('tiktok_cs.sync_max_pages', 50);

        $pageToken   = null;
        $totalSynced = 0;
        $totalPages  = 0;

        do {
            $totalPages++;

            $path      = '/customer_service/202309/conversations';
            $timestamp = time();

            $queryParams = [
                'app_key'     => $this->appKey,
                'timestamp'   => $timestamp,
                'shop_cipher' => $shopCipher,
                'page_size'   => $pageSize,
            ];

            if ($pageToken) {
                $queryParams['page_token'] = $pageToken;
            }

            $sign = $this->tiktokApiService->buildSign($path, $queryParams);

            $queryParams['sign']         = $sign;
            $queryParams['access_token'] = $accessToken;

            $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

            $response = Http::timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders([
                    'Content-Type'       => 'application/json',
                    'x-tts-access-token' => $accessToken,
                ])
                ->get($url);

            $data = $response->json();

            if (($data['code'] ?? -1) !== 0) {
                Log::error('TikTokCS: syncAllConversations page failed', [
                    'page'     => $totalPages,
                    'response' => $data,
                ]);
                break;
            }

            $conversations = $data['data']['conversations'] ?? [];
            $pageToken     = $data['data']['next_page_token'] ?? null;

            foreach ($conversations as $conv) {
                $convId = $conv['conversation_id'] ?? $conv['id'] ?? null;
                if (!$convId) continue;

                TikTokConversation::updateOrCreate(
                    [
                        'account_id'      => $account->id,
                        'conversation_id' => $convId,
                    ],
                    [
                        'buyer_user_id'     => $conv['buyer_uid'] ?? $conv['buyer_user_id'] ?? null,
                        'buyer_nickname'    => $conv['buyer_nickname'] ?? null,
                        'buyer_avatar_url'  => $conv['buyer_avatar'] ?? null,
                        'status'            => TikTokConversation::STATUS_ACTIVE,
                        'tiktok_created_at' => $this->toCarbonFromTimestamp($conv['create_time'] ?? null),
                        'last_message_at'   => $this->toCarbonFromTimestamp($conv['last_message_time'] ?? null),
                    ]
                );

                $totalSynced++;
            }

            if ($pageToken) {
                usleep(300000); // Rate limiting 300ms
            }
        } while ($pageToken && $totalPages < $maxPages);

        return [
            'synced' => $totalSynced,
            'pages'  => $totalPages,
        ];
    }

    /* ===================================================================
     *  SEND MESSAGE — Kirim balasan ke pembeli via TikTok API
     * =================================================================== */

    /**
     * Kirim pesan ke percakapan TikTok.
     *
     * @param  AccountShopTiktok $account
     * @param  string            $conversationId  TikTok conversation ID
     * @param  string            $message          Isi pesan (text) atau URL (image/video)
     * @param  string            $contentType      text|image|video
     * @return array             API response data
     *
     * @throws \RuntimeException
     */
    public function sendMessage(
        AccountShopTiktok $account,
        string $conversationId,
        string $message,
        string $contentType = 'text'
    ): array {
        $accessToken = $account->access_token;
        $shopCipher  = $account->shop_cipher;

        $path      = "/customer_service/202309/conversations/{$conversationId}/messages";
        $timestamp = time();

        $queryParams = [
            'app_key'     => $this->appKey,
            'timestamp'   => $timestamp,
            'shop_cipher' => $shopCipher,
        ];

        // Build body sesuai content type
        $body = match ($contentType) {
            'image' => [
                'content' => [
                    'type'  => 'image',
                    'image' => ['url' => $message],
                ],
            ],
            'video' => [
                'content' => [
                    'type'  => 'video',
                    'video' => ['url' => $message],
                ],
            ],
            default => [
                'content' => [
                    'type' => 'text',
                    'text' => ['text' => $message],
                ],
            ],
        };

        $bodyJson = json_encode($body);

        $sign = $this->tiktokApiService->buildSign($path, $queryParams, $bodyJson);

        $queryParams['sign']         = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = $this->apiBase . $path . '?' . http_build_query($queryParams);

        $response = Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders([
                'Content-Type'       => 'application/json',
                'x-tts-access-token' => $accessToken,
            ])
            ->withBody($bodyJson, 'application/json')
            ->post($url);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            Log::error('TikTokCS: sendMessage failed', [
                'conversation_id' => $conversationId,
                'response'        => $data,
            ]);
            throw new \RuntimeException(
                'TikTok CS Send Message Error [' . ($data['code'] ?? '?') . ']: '
                    . ($data['message'] ?? 'Unknown error')
            );
        }

        // Simpan pesan yang terkirim ke DB lokal
        $conversation = TikTokConversation::where('account_id', $account->id)
            ->where('conversation_id', $conversationId)
            ->first();

        if ($conversation) {
            $sentMsgId = $data['data']['message_id'] ?? ('sent_' . time());

            TikTokMessage::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'message_id'      => $sentMsgId,
                ],
                [
                    'sender_type'       => TikTokMessage::SENDER_CUSTOMER_SERVICE,
                    'content_type'      => $contentType,
                    'content'           => $message,
                    'tiktok_created_at' => now(),
                ]
            );

            $conversation->update(['last_message_at' => now()]);
        }

        Log::info('TikTokCS: Message sent', [
            'conversation_id' => $conversationId,
            'content_type'    => $contentType,
        ]);

        return $data['data'] ?? [];
    }

    /* ===================================================================
     *  WEBHOOK SIGNATURE VERIFICATION
     * =================================================================== */

    /**
     * Verifikasi signature webhook dari TikTok.
     *
     * TikTok mengirim header `Authorization` berisi HMAC-SHA256
     * dari raw body menggunakan webhook secret sebagai key.
     *
     * @param  string $rawBody    Raw request body (JSON string)
     * @param  string $signature  Signature dari header Authorization
     * @return bool
     */
    public static function verifySignature(string $rawBody, string $signature): bool
    {
        $secret = config('tiktok_cs.webhook_secret', '');

        if (empty($secret)) {
            Log::warning('TikTokCS: Webhook secret not configured, skipping verification');
            return true; // Allow if not configured (development mode)
        }

        $computed = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($computed, $signature);
    }

    /* ===================================================================
     *  PRIVATE HELPERS
     * =================================================================== */

    /**
     * Resolve AccountShopTiktok berdasar shop_id dari payload.
     */
    private function resolveAccount(?string $shopId): ?AccountShopTiktok
    {
        if (empty($shopId)) {
            return null;
        }

        return AccountShopTiktok::where('shop_id', $shopId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Extract readable content dari berbagai content type.
     */
    private function extractMessageContent(array $contentData, string $contentType): ?string
    {
        return match ($contentType) {
            'text'         => $contentData['text']['text']
                ?? $contentData['text']
                ?? ($contentData['content'] ?? null),
            'image'        => $contentData['image']['url']
                ?? ($contentData['image_url'] ?? json_encode($contentData)),
            'video'        => $contentData['video']['url']
                ?? ($contentData['video_url'] ?? json_encode($contentData)),
            'product_card' => json_encode($contentData['product_card'] ?? $contentData),
            'order_card'   => json_encode($contentData['order_card'] ?? $contentData),
            'emoji'        => $contentData['emoji']['code'] ?? ($contentData['emoji'] ?? null),
            'sticker'      => $contentData['sticker']['url'] ?? json_encode($contentData),
            default        => json_encode($contentData),
        };
    }

    /**
     * Convert API timestamp ke Carbon (handle Unix seconds & milliseconds).
     */
    private function toCarbonFromTimestamp($ts): ?Carbon
    {
        if (empty($ts)) return null;
        $ts = (int) $ts;
        if ($ts > 1000000000000) {
            $ts = (int) floor($ts / 1000);
        }
        return Carbon::createFromTimestampUTC($ts)
            ->setTimezone(config('app.timezone') ?: date_default_timezone_get());
    }
}
