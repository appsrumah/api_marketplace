<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'label',
        'group',
        'description',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────

    /** Roles yang memiliki permission ini */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    // ─── Daftar grup permission yang valid ────────────────────────────────

    const GROUPS = [
        'users'      => 'Manajemen Pengguna',
        'channels'   => 'Akun Channel Marketplace',
        'products'   => 'Produk',
        'orders'     => 'Pesanan',
        'stock'      => 'Stok',
        'warehouses' => 'Gudang/Outlet',
        'reports'    => 'Laporan',
        'settings'   => 'Pengaturan Sistem',
    ];

    // ─── Daftar semua permission yang tersedia ────────────────────────────

    const ALL = [
        // ── users ──────────────────────────────────────────────────────────
        'users.view'          => 'Lihat Daftar Pengguna',
        'users.create'        => 'Tambah Pengguna',
        'users.edit'          => 'Edit Pengguna',
        'users.delete'        => 'Hapus Pengguna',
        'users.toggle_active' => 'Aktifkan/Nonaktifkan Pengguna',

        // ── channels ───────────────────────────────────────────────────────
        'channels.view'       => 'Lihat Akun Channel',
        'channels.connect'    => 'Hubungkan Akun Channel',
        'channels.disconnect' => 'Putuskan Akun Channel',
        'channels.sync'       => 'Sinkronisasi Produk Channel',

        // ── products ───────────────────────────────────────────────────────
        'products.view'       => 'Lihat Produk',
        'products.create'     => 'Tambah Produk',
        'products.edit'       => 'Edit Produk',
        'products.delete'     => 'Hapus Produk',
        'products.import'     => 'Import Produk',
        'products.export'     => 'Export Produk',

        // ── orders ─────────────────────────────────────────────────────────
        'orders.view'         => 'Lihat Pesanan',
        'orders.process'      => 'Proses Pesanan',
        'orders.cancel'       => 'Batalkan Pesanan',
        'orders.export'       => 'Export Pesanan',

        // ── stock ──────────────────────────────────────────────────────────
        'stock.view'          => 'Lihat Stok',
        'stock.sync'          => 'Sinkronisasi Stok',
        'stock.import'        => 'Import Stok',
        'stock.export'        => 'Export Stok',

        // ── warehouses ─────────────────────────────────────────────────────
        'warehouses.view'     => 'Lihat Gudang',
        'warehouses.manage'   => 'Kelola Gudang (CRUD)',

        // ── reports ────────────────────────────────────────────────────────
        'reports.view'        => 'Lihat Laporan',
        'reports.export'      => 'Export Laporan',

        // ── settings ───────────────────────────────────────────────────────
        'settings.view'       => 'Lihat Pengaturan',
        'settings.manage'     => 'Ubah Pengaturan Sistem',
    ];
}
