<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Audit trail semua aktivitas penting di sistem.
 * Bisa digunakan bersama trait Loggable pada model lain.
 */
class ActivityLog extends Model
{
    // Tidak perlu updated_at karena log tidak diubah
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Static Factory Helpers ────────────────────────────────────────────

    /**
     * Catat aktivitas dari request yang sedang berjalan.
     *
     * @param string      $action      Format: resource.verb, misal: user.login
     * @param string|null $description Keterangan bebas
     * @param mixed|null  $subject     Model yang terdampak (opsional)
     * @param array|null  $oldValues
     * @param array|null  $newValues
     * @param string      $level       info|warning|critical
     */
    public static function record(
        string  $action,
        ?string $description = null,
        mixed   $subject = null,
        ?array  $oldValues = null,
        ?array  $newValues = null,
        string  $level = 'info'
    ): self {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return self::create([
            'user_id'      => $user?->id,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'old_values'   => $oldValues,
            'new_values'   => $newValues,
            'description'  => $description,
            'level'        => $level,
        ]);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }
}
