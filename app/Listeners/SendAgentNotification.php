<?php

namespace App\Listeners;

use App\Events\NewConversationReceived;
use App\Events\NewMessageReceived;
use App\Models\ActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Listener: simpan notifikasi ke DB dan kirim ke agent CS.
 *
 * Menghandle 2 event:
 *   - NewConversationReceived (type 13)
 *   - NewMessageReceived (type 14)
 *
 * Event itu sendiri sudah implement ShouldBroadcast,
 * jadi realtime push via websocket otomatis berjalan.
 * Listener ini fokus pada penyimpanan notifikasi & log audit.
 */
class SendAgentNotification implements ShouldQueue
{
    public int $tries = 2;

    /**
     * Handle event NewConversationReceived.
     */
    public function handleNewConversation(NewConversationReceived $event): void
    {
        $conversation = $event->conversation;

        Log::info('SendAgentNotification: New conversation', [
            'conversation_id' => $conversation->conversation_id,
            'account_id'      => $conversation->account_id,
            'buyer'           => $conversation->buyer_nickname,
        ]);

        // Catat di activity log
        ActivityLog::record(
            'tiktok_cs.new_conversation',
            "Percakapan baru dari {$conversation->buyer_nickname} di akun #{$conversation->account_id}"
        );

        // TODO: Kirim notifikasi ke agent CS yang online
        // Contoh: Notification::send($agents, new NewChatNotification($conversation));
        // Implementasi tergantung sistem notifikasi yang digunakan (database, mail, Slack, dll)
    }

    /**
     * Handle event NewMessageReceived.
     */
    public function handleNewMessage(NewMessageReceived $event): void
    {
        $message      = $event->message;
        $conversation = $event->conversation;

        // Hanya proses pesan dari buyer (bukan dari agent sendiri)
        if (!$message->isBuyerMessage()) {
            return;
        }

        Log::info('SendAgentNotification: New buyer message', [
            'conversation_id' => $conversation->conversation_id,
            'message_id'      => $message->message_id,
            'content_type'    => $message->content_type,
        ]);

        // Increment unread counter di conversation
        $conversation->incrementUnread();

        // Catat di activity log
        ActivityLog::record(
            'tiktok_cs.new_message',
            "Pesan baru [{$message->content_type}] dari {$conversation->buyer_nickname}: {$message->preview}"
        );

        // TODO: Kirim push notification ke assigned agent (jika ada)
        // if ($conversation->assigned_agent_id) {
        //     $agent = $conversation->agent;
        //     Notification::send($agent, new IncomingMessageNotification($message, $conversation));
        // }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events): array
    {
        return [
            NewConversationReceived::class => 'handleNewConversation',
            NewMessageReceived::class      => 'handleNewMessage',
        ];
    }
}
