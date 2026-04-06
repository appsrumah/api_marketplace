<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Permission;

class Role extends Model
{
    // ─── Role Slugs (konstanta) ────────────────────────────────────────────

    const SUPER_ADMIN  = 'super_admin';
    const ADMIN        = 'admin';
    const MANAGER      = 'manager';
    const STAFF_ADMIN  = 'staff_admin';
    const OPERATOR     = 'operator';
    const FINANCE      = 'finance';
    const CS           = 'cs';

    // ─── Mass Assignment ───────────────────────────────────────────────────

    protected $fillable = [
        'name',
        'label',
        'description',
        'level',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level'     => 'integer',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Users yang memiliki role ini */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /** Permissions yang dimiliki role ini */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /** Apakah role ini memiliki permission tertentu? */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }

    /** Daftar nama permission (cached via relations) */
    public function permissionNames(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, string $direction = 'desc')
    {
        return $query->orderBy('level', $direction);
    }
}
