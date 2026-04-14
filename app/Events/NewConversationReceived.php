<?php

namespace App\Events;

use App\Models\TikTokConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: percakapan baru diterima dari webhook TikTok (type 13).
 *
 * Di-broadcast ke channel private agent CS untuk notifikasi realtime.
 */
class NewConversationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TikTokConversation $conversation,
        public readonly array              $rawPayload = [],
    ) {}

    /**
     * Channel broadcast — private per account (multi-shop).
     */
    public function broadcastOn(): array
    {
        $prefix = config('tiktok_cs.broadcast_channel_prefix', 'tiktok-cs');

        return [
            new Channel("{$prefix}.account.{$this->conversation->account_id}"),
        ];
    }

    /**
     * Nama event di sisi client (JavaScript).
     */
    public function broadcastAs(): string
    {
        return 'conversation.new';
    }

    /**
     * Data yang dikirim ke client.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->conversation_id,
            'buyer_nickname'  => $this->conversation->buyer_nickname,
            'buyer_avatar'    => $this->conversation->buyer_avatar_url,
            'account_id'      => $this->conversation->account_id,
            'created_at'      => $this->conversation->tiktok_created_at?->toIso8601String(),
        ];
    }
}
