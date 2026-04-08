<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Urutan penting:
     *  1. MarketplaceChannelSeeder  — master channels (tidak ada FK dependency)
     *  2. WarehouseSeeder           — master warehouse default
     *  3. RolePermissionSeeder      — master roles + permissions + sync role_id ke users
     *  4. SystemSettingSeeder       — konfigurasi global aplikasi
     */
    public function run(): void
    {
        $this->call([
            MarketplaceChannelSeeder::class,
            WarehouseSeeder::class,
            RolePermissionSeeder::class,
            SystemSettingSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉  Semua master data berhasil di-seed!');
        $this->command->info('   ✔ Marketplace Channels (TikTok, Shopee, Tokopedia, Lazada, Blibli, dst.)');
        $this->command->info('   ✔ Warehouses (Gudang Utama default)');
        $this->command->info('   ✔ Roles (7 role) + Permissions (30 permission) + Matrix hak akses');
        $this->command->info('   ✔ System Settings (konfigurasi default aplikasi)');
    }
}
