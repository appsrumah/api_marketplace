<?php

namespace App\Models;

use App\Models\Role as RoleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ─── Role Constants ───────────────────────────────────────────────────────

    const ROLE_SUPER_ADMIN  = 'super_admin';
    const ROLE_ADMIN        = 'admin';
    const ROLE_MANAGER      = 'manager';
    const ROLE_STAFF_ADMIN  = 'staff_admin';
    const ROLE_OPERATOR     = 'operator';
    const ROLE_FINANCE      = 'finance';
    const ROLE_CS           = 'cs';

    const ROLES = [
        'super_admin' => 'Super Admin',
        'admin'       => 'Admin',
        'manager'     => 'Manager',
        'staff_admin' => 'Staff Admin',
        'operator'    => 'Operator',
        'finance'     => 'Finance',
        'cs'          => 'Customer Service',
    ];

    const ROLE_COLORS = [
        'super_admin' => 'bg-violet-100 text-violet-700',
        'admin'       => 'bg-blue-100 text-blue-700',
        'manager'     => 'bg-indigo-100 text-indigo-700',
        'staff_admin' => 'bg-cyan-100 text-cyan-700',
        'operator'    => 'bg-slate-100 text-slate-600',
        'finance'     => 'bg-emerald-100 text-emerald-700',
        'cs'          => 'bg-amber-100 text-amber-700',
    ];

    // ─── Mass Assignment ──────────────────────────────────────────────────────

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'role_id',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Sub-akun yang dibuat oleh user ini */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * Role formal dari tabel roles (sistem permission baru).
     * Nama method sengaja roleModel() agar tidak konflik dengan atribut `role` (enum).
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    /** Akun TikTok yang dimiliki user ini */
    public function tiktokAccounts()
    {
        return $this->hasMany(AccountShopTiktok::class, 'user_id');
    }

    /** Akun channel general yang dimiliki user ini */
    public function channelAccounts()
    {
        return $this->hasMany(ChannelAccount::class, 'user_id');
    }

    /** Warehouse yang dibuat oleh user ini */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'created_by');
    }

    /** Activity log milik user ini */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
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

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isStaffAdmin(): bool
    {
        return $this->role === self::ROLE_STAFF_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
    }

    public function isCs(): bool
    {
        return $this->role === self::ROLE_CS;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Ambil daftar nama permission user ini melalui role_id.
     * Super Admin otomatis mendapat semua permission.
     */
    public function getPermissions(): Collection
    {
        if ($this->isSuperAdmin()) {
            return collect(array_keys(\App\Models\Permission::ALL));
        }
        return $this->roleModel?->permissions->pluck('name') ?? collect();
    }

    /**
     * Cek apakah user memiliki permission tertentu.
     * Super Admin selalu true.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->getPermissions()->contains($permission);
    }

    // ─── Access Control (Feature Flags) ──────────────────────────────────────
    // Metode-metode ini menjadi wrapper ke sistem permission baru.
    // Tetap dipertahankan untuk backward-compatibility dengan kode lama.

    /** Kelola pengguna (tambah/edit/hapus) */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('users.create')
            || $this->hasPermission('users.edit')
            || $this->hasPermission('users.delete');
    }

    /** Lihat daftar pengguna */
    public function canViewUsers(): bool
    {
        return $this->hasPermission('users.view');
    }

    /** Kelola akun marketplace (hubungkan/putuskan) */
    public function canManageMarketplaceAccounts(): bool
    {
        return $this->hasPermission('channels.connect')
            || $this->hasPermission('channels.disconnect');
    }

    /** Kelola produk (tambah/edit/hapus) */
    public function canManageProducts(): bool
    {
        return $this->hasPermission('products.create')
            || $this->hasPermission('products.edit')
            || $this->hasPermission('products.delete');
    }

    /** Sync stok ke channel */
    public function canSyncStock(): bool
    {
        return $this->hasPermission('stock.sync');
    }

    /** Lihat laporan */
    public function canViewReports(): bool
    {
        return $this->hasPermission('reports.view');
    }

    /** Kelola gudang/warehouse */
    public function canManageWarehouses(): bool
    {
        return $this->hasPermission('warehouses.manage');
    }

    /** Ubah pengaturan sistem */
    public function canManageSettings(): bool
    {
        return $this->hasPermission('settings.manage');
    }
}
