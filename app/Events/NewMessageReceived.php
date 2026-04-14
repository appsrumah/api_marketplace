<?php

namespace App\Events;

use App\Models\TikTokConversation;
use App\Models\TikTokMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: pesan baru diterima dari webhook TikTok (type 14).
 *
 * Di-broadcast ke channel private agent CS untuk update chat realtime.
 */
class NewMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TikTokMessage      $message,
        public readonly TikTokConversation $conversation,
        public readonly array              $rawPayload = [],
    ) {}

    /**
     * Channel broadcast — private per account.
     */
    public function broadcastOn(): array
    {
        $prefix = config('tiktok_cs.broadcast_channel_prefix', 'tiktok-cs');

        return [
            new Channel("{$prefix}.account.{$this->conversation->account_id}"),
            new Channel("{$prefix}.conversation.{$this->conversation->id}"),
        ];
    }

    /**
     * Nama event di sisi client.
     */
    public function broadcastAs(): string
    {
        return 'message.new';
    }

    /**
     * Data yang dikirim ke client.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id'      => $this->message->message_id,
            'conversation_id' => $this->conversation->conversation_id,
            'sender_type'     => $this->message->sender_type,
            'content_type'    => $this->message->content_type,
            'content'         => $this->message->content,
            'preview'         => $this->message->preview,
            'buyer_nickname'  => $this->conversation->buyer_nickname,
            'account_id'      => $this->conversation->account_id,
            'created_at'      => $this->message->tiktok_created_at?->toIso8601String(),
        ];
    }
}
