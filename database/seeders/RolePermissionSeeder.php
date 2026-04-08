<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * ─── Struktur Role Platform Omni-Channel ────────────────────────────────
     *
     * Level  | Slug         | Label           | Deskripsi
     * -------|------------- |-----------------|-----------------------------------
     *  100   | super_admin  | Super Admin     | Akses penuh, pemilik sistem
     *   80   | admin        | Admin           | Akses penuh kecuali pengaturan sistem
     *   60   | manager      | Manager         | Kelola tim, pantau laporan, approve
     *   40   | staff_admin  | Staff Admin     | Input data, kelola produk & pesanan
     *   35   | finance      | Finance         | Laporan keuangan, billing
     *   25   | cs           | Customer Service| Pesanan, komunikasi pelanggan
     *   20   | operator     | Operator        | Sync stok, lihat produk & pesanan
     */
    public function run(): void
    {
        // ── 1. Buat Roles ─────────────────────────────────────────────────

        $roles = [
            [
                'name'        => 'super_admin',
                'label'       => 'Super Admin',
                'description' => 'Akses penuh ke seluruh sistem. Tidak bisa dihapus.',
                'level'       => 100,
                'is_system'   => true,
                'is_active'   => true,
            ],
            [
                'name'        => 'admin',
                'label'       => 'Admin',
                'description' => 'Akses penuh kecuali pengaturan sistem inti.',
                'level'       => 80,
                'is_system'   => true,
                'is_active'   => true,
            ],
            [
                'name'        => 'manager',
                'label'       => 'Manager',
                'description' => 'Kelola tim operasional, pantau dashboard & laporan, approve pesanan penting.',
                'level'       => 60,
                'is_system'   => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'staff_admin',
                'label'       => 'Staff Admin',
                'description' => 'Input & kelola produk, pesanan harian. Tidak bisa ubah setting atau kelola user.',
                'level'       => 40,
                'is_system'   => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'finance',
                'label'       => 'Finance',
                'description' => 'Akses laporan keuangan, export data transaksi. Read-only untuk produk & pesanan.',
                'level'       => 35,
                'is_system'   => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'cs',
                'label'       => 'Customer Service',
                'description' => 'Proses & batalkan pesanan, komunikasi pelanggan. Tidak bisa kelola produk.',
                'level'       => 25,
                'is_system'   => false,
                'is_active'   => true,
            ],
            [
                'name'        => 'operator',
                'label'       => 'Operator',
                'description' => 'Operasi terbatas: sync stok, lihat produk & pesanan. Tidak bisa ubah data.',
                'level'       => 20,
                'is_system'   => true,
                'is_active'   => true,
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(['name' => $data['name']], $data);
        }

        // ── 2. Buat Permissions ───────────────────────────────────────────

        foreach (Permission::ALL as $name => $label) {
            [$group] = explode('.', $name, 2);
            Permission::updateOrCreate(
                ['name' => $name],
                ['name' => $name, 'label' => $label, 'group' => $group]
            );
        }

        // ── 3. Assign Permissions ke Role ─────────────────────────────────

        $allPerms = Permission::pluck('id', 'name');

        $matrix = [
            // Super Admin: semua permission (ditangani di User::hasPermission via isSuperAdmin())
            // tapi tetap di-assign agar konsisten di DB
            'super_admin' => array_keys(Permission::ALL),

            // Admin: semua kecuali settings.manage
            'admin' => array_keys(array_filter(
                Permission::ALL,
                fn($_, $k) => $k !== 'settings.manage',
                ARRAY_FILTER_USE_BOTH
            )),

            // Manager: view all + orders process/cancel + stock.* + reports.*
            'manager' => [
                'users.view',
                'channels.view',
                'channels.sync',
                'products.view',
                'products.export',
                'orders.view',
                'orders.process',
                'orders.cancel',
                'orders.export',
                'stock.view',
                'stock.sync',
                'stock.export',
                'warehouses.view',
                'reports.view',
                'reports.export',
                'settings.view',
            ],

            // Staff Admin: produk CRUD + pesanan proses + stock view/sync
            'staff_admin' => [
                'channels.view',
                'channels.sync',
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'products.import',
                'products.export',
                'orders.view',
                'orders.process',
                'stock.view',
                'stock.sync',
                'stock.import',
                'stock.export',
                'warehouses.view',
                'reports.view',
            ],

            // Finance: laporan + lihat pesanan + lihat produk/stok
            'finance' => [
                'products.view',
                'products.export',
                'orders.view',
                'orders.export',
                'stock.view',
                'stock.export',
                'warehouses.view',
                'reports.view',
                'reports.export',
            ],

            // Customer Service: pesanan + lihat produk/stok
            'cs' => [
                'channels.view',
                'products.view',
                'orders.view',
                'orders.process',
                'orders.cancel',
                'orders.export',
                'stock.view',
                'warehouses.view',
            ],

            // Operator: sync stok + lihat saja
            'operator' => [
                'channels.view',
                'channels.sync',
                'products.view',
                'orders.view',
                'stock.view',
                'stock.sync',
                'warehouses.view',
            ],
        ];

        foreach ($matrix as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                continue;
            }

            $ids = collect($permissions)
                ->filter(fn($p) => isset($allPerms[$p]))
                ->map(fn($p) => $allPerms[$p])
                ->toArray();

            $role->permissions()->sync($ids);
        }

        // ── 4. Update role_id di tabel users berdasarkan role enum ────────

        $roleMap = Role::pluck('id', 'name');

        DB::table('users')->get()->each(function ($user) use ($roleMap) {
            // Map role enum lama ke slug baru (sama persis)
            $roleId = $roleMap[$user->role] ?? null;

            if ($roleId && !$user->role_id) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['role_id' => $roleId]);
            }
        });

        $this->command->info('✅  Roles, Permissions, dan Role-User mapping berhasil di-seed.');
    }
}
