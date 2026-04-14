<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TikTokMessage extends Model
{
    protected $table = 'tiktok_messages';

    protected $fillable = [
        'conversation_id',
        'message_id',
        'sender_type',
        'sender_id',
        'content_type',
        'content',
        'metadata',
        'is_read',
        'tiktok_created_at',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id'   => 'integer',
            'metadata'          => 'array',
            'is_read'           => 'boolean',
            'tiktok_created_at' => 'datetime',
        ];
    }

    // ─── Sender Type Constants ─────────────────────────────────────────────

    const SENDER_BUYER            = 'BUYER';
    const SENDER_CUSTOMER_SERVICE = 'CUSTOMER_SERVICE';
    const SENDER_SHOP             = 'SHOP';
    const SENDER_SYSTEM           = 'SYSTEM';
    const SENDER_ROBOT            = 'ROBOT';

    // ─── Content Type Constants ────────────────────────────────────────────

    const CONTENT_TEXT         = 'text';
    const CONTENT_IMAGE        = 'image';
    const CONTENT_VIDEO        = 'video';
    const CONTENT_PRODUCT_CARD = 'product_card';
    const CONTENT_ORDER_CARD   = 'order_card';
    const CONTENT_EMOJI        = 'emoji';
    const CONTENT_STICKER      = 'sticker';
    const CONTENT_FILE         = 'file';

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Percakapan induk */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TikTokConversation::class, 'conversation_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────

    /** Hanya pesan dari pembeli */
    public function scopeFromBuyer($query)
    {
        return $query->where('sender_type', self::SENDER_BUYER);
    }

    /** Pesan belum dibaca */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /** Apakah pesan ini dari pembeli? */
    public function isBuyerMessage(): bool
    {
        return $this->sender_type === self::SENDER_BUYER;
    }

    /** Ambil text preview untuk notifikasi (max 100 char) */
    public function getPreviewAttribute(): string
    {
        if ($this->content_type !== self::CONTENT_TEXT) {
            return "[{$this->content_type}]";
        }

        return \Illuminate\Support\Str::limit($this->content ?? '', 100);
    }
}
