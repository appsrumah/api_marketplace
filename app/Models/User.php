<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ─── Role Constants ───────────────────────────────────────────────────────

    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_OPERATOR    = 'operator';

    const ROLES = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'operator'    => 'Operator',
    ];

    const ROLE_COLORS = [
        'super_admin' => 'bg-violet-100 text-violet-700',
        'admin'       => 'bg-blue-100 text-blue-700',
        'operator'    => 'bg-slate-100 text-slate-600',
    ];

    // ─── Mass Assignment ──────────────────────────────────────────────────────

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'created_by',
        'avatar',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ─── Casts ────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** Dibuat oleh Super Admin ini */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Sub-akun yang dibuat oleh user ini */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // ─── Computed Attributes ─────────────────────────────────────────────────

    /** Label peran (e.g. "Super Admin") */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst(str_replace('_', ' ', $this->role));
    }

    /** Warna badge peran untuk CSS classes */
    public function getRoleColorAttribute(): string
    {
        return self::ROLE_COLORS[$this->role] ?? 'bg-slate-100 text-slate-600';
    }

    /** Inisial nama (2 huruf) */
    public function getInitialsAttribute(): string
    {
        $words = array_values(array_filter(explode(' ', trim($this->name))));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    // ─── Role Checks ─────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // ─── Access Control (Feature Flags) ──────────────────────────────────────

    /** Hanya Super Admin yang bisa kelola pengguna */
    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin();
    }

    /** Super Admin & Admin bisa kelola akun marketplace */
    public function canManageMarketplaceAccounts(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /** Super Admin & Admin bisa kelola produk */
    public function canManageProducts(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /** Semua role bisa lihat & trigger sync stok */
    public function canSyncStock(): bool
    {
        return true;
    }

    /** Super Admin & Admin bisa akses laporan */
    public function canViewReports(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }
}
