<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TikTokConversation extends Model
{
    protected $table = 'tiktok_conversations';

    protected $fillable = [
        'account_id',
        'conversation_id',
        'buyer_user_id',
        'buyer_nickname',
        'buyer_avatar_url',
        'status',
        'assigned_agent_id',
        'unread_count',
        'last_message_at',
        'tiktok_created_at',
    ];

    protected function casts(): array
    {
        return [
            'account_id'        => 'integer',
            'assigned_agent_id' => 'integer',
            'unread_count'      => 'integer',
            'last_message_at'   => 'datetime',
            'tiktok_created_at' => 'datetime',
        ];
    }

    // ─── Status Constants ──────────────────────────────────────────────────

    const STATUS_ACTIVE   = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_CLOSED   = 'closed';

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Account TikTok Shop */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountShopTiktok::class, 'account_id');
    }

    /** Agent CS yang di-assign */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /** Semua pesan dalam percakapan */
    public function messages(): HasMany
    {
        return $this->hasMany(TikTokMessage::class, 'conversation_id');
    }

    /** Pesan terakhir */
    public function latestMessage()
    {
        return $this->hasOne(TikTokMessage::class, 'conversation_id')->latestOfMany('tiktok_created_at');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────

    /** Hanya percakapan aktif */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /** Percakapan dengan pesan belum dibaca */
    public function scopeWithUnread($query)
    {
        return $query->where('unread_count', '>', 0);
    }

    /** Filter berdasar account TikTok */
    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /** Multi-tenant scope: filter berdasar user login */
    public function scopeForUser($query, ?\App\Models\User $user = null)
    {
        $resolved = $user ?? \Illuminate\Support\Facades\Auth::user();

        if ($resolved instanceof User && !$resolved->isSuperAdmin()) {
            $accountIds = AccountShopTiktok::where('user_id', $resolved->id)->pluck('id');
            return $query->whereIn('account_id', $accountIds);
        }

        return $query;
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    /** Increment unread count */
    public function incrementUnread(): void
    {
        $this->increment('unread_count');
        $this->update(['last_message_at' => now()]);
    }

    /** Reset unread count (agent membuka chat) */
    public function markAsRead(): void
    {
        $this->update(['unread_count' => 0]);
        $this->messages()->where('is_read', false)->update(['is_read' => true]);
    }
}
