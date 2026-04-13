<?php

/**
 * migrate-v2.php — Jalankan semua pending migrations (Omni-Channel) via browser
 * Upload ke: public_html/app.oleh2indonesia.com/public/migrate-v2.php
 * Buka sekali di browser, lalu HAPUS file ini setelah selesai.
 *
 * Cakupan:
 * - Tabel master: roles, permissions, role_permissions
 * - Tabel omni: marketplace_channels, warehouses, channel_accounts
 * - Tabel activity_logs, system_settings
 * - Tabel orders, order_items, product_details
 * - ALTER: users (role_id), account_shop_tiktok (channel_id, warehouse_id, user_id)
 * - ALTER: produk_saya (channel_id)
 * - Seeder: roles + permissions + channels + warehouse + settings
 */

define('LARAVEL_ROOT', dirname(__DIR__));
require LARAVEL_ROOT . '/vendor/autoload.php';

$app    = require LARAVEL_ROOT . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$results = [];
$nextBatch = function () {
    return DB::table('migrations')->max('batch') + 1;
};
$recordMigration = function (string $name) use ($nextBatch) {
    if (!DB::table('migrations')->where('migration', $name)->exists()) {
        DB::table('migrations')->insert(['migration' => $name, 'batch' => $nextBatch()]);
    }
};

// ═══════════════════════════════════════════════════════════════════════════════
// 1. ROLES TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('roles')) {
        DB::statement("
            CREATE TABLE roles (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(50)     NOT NULL UNIQUE,
                label       VARCHAR(100)    NOT NULL,
                level       TINYINT UNSIGNED NOT NULL DEFAULT 0,
                description TEXT            NULL,
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100001_create_roles_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel roles dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel roles sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat roles: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 2. PERMISSIONS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('permissions')) {
        DB::statement("
            CREATE TABLE permissions (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(100)    NOT NULL UNIQUE,
                label       VARCHAR(150)    NOT NULL,
                `group`     VARCHAR(50)     NOT NULL DEFAULT 'general',
                description TEXT            NULL,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL,
                INDEX idx_permissions_group (`group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100002_create_permissions_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel permissions dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel permissions sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat permissions: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 3. ROLE_PERMISSIONS PIVOT TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('role_permissions')) {
        DB::statement("
            CREATE TABLE role_permissions (
                role_id       BIGINT UNSIGNED NOT NULL,
                permission_id BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (role_id, permission_id),
                INDEX idx_rp_permission_id (permission_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100003_create_role_permissions_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel role_permissions dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel role_permissions sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat role_permissions: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 4. MARKETPLACE_CHANNELS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('marketplace_channels')) {
        DB::statement("
            CREATE TABLE marketplace_channels (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                code        VARCHAR(30)     NOT NULL UNIQUE,
                name        VARCHAR(100)    NOT NULL,
                logo_url    VARCHAR(500)    NULL,
                color       VARCHAR(7)      NULL DEFAULT '#6b7280',
                is_active   TINYINT(1)      NOT NULL DEFAULT 1,
                sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100004_create_marketplace_channels_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel marketplace_channels dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel marketplace_channels sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat marketplace_channels: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 5. WAREHOUSES TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('warehouses')) {
        DB::statement("
            CREATE TABLE warehouses (
                id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name          VARCHAR(100)    NOT NULL,
                code          VARCHAR(30)     NOT NULL UNIQUE,
                address       TEXT            NULL,
                city          VARCHAR(100)    NULL,
                province      VARCHAR(100)    NULL,
                postal_code   VARCHAR(10)     NULL,
                phone         VARCHAR(20)     NULL,
                pos_outlet_id INT UNSIGNED    NULL COMMENT 'ID outlet di POS',
                is_default    TINYINT(1)      NOT NULL DEFAULT 0,
                is_active     TINYINT(1)      NOT NULL DEFAULT 1,
                created_at    TIMESTAMP       NULL,
                updated_at    TIMESTAMP       NULL,
                INDEX idx_warehouses_pos_outlet (pos_outlet_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100005_create_warehouses_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel warehouses dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel warehouses sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat warehouses: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 6. CHANNEL_ACCOUNTS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('channel_accounts')) {
        DB::statement("
            CREATE TABLE channel_accounts (
                id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                channel_id      BIGINT UNSIGNED NOT NULL,
                user_id         BIGINT UNSIGNED NULL,
                warehouse_id    BIGINT UNSIGNED NULL,
                account_name    VARCHAR(255)    NOT NULL,
                shop_id         VARCHAR(100)    NULL,
                shop_name       VARCHAR(255)    NULL,
                access_token    TEXT            NULL,
                refresh_token   TEXT            NULL,
                token_expires_at TIMESTAMP      NULL,
                status          ENUM('active','inactive','expired') NOT NULL DEFAULT 'active',
                meta            JSON            NULL,
                last_sync_at    TIMESTAMP       NULL,
                created_at      TIMESTAMP       NULL,
                updated_at      TIMESTAMP       NULL,
                INDEX idx_ca_channel_id (channel_id),
                INDEX idx_ca_user_id (user_id),
                INDEX idx_ca_warehouse_id (warehouse_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100006_create_channel_accounts_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel channel_accounts dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel channel_accounts sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat channel_accounts: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 7. ACTIVITY_LOGS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('activity_logs')) {
        DB::statement("
            CREATE TABLE activity_logs (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id     BIGINT UNSIGNED NULL,
                action      VARCHAR(100)    NOT NULL,
                description TEXT            NULL,
                metadata    JSON            NULL,
                ip_address  VARCHAR(45)     NULL,
                user_agent  VARCHAR(500)    NULL,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL,
                INDEX idx_al_user_id (user_id),
                INDEX idx_al_action (action),
                INDEX idx_al_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100007_create_activity_logs_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel activity_logs dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel activity_logs sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat activity_logs: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 8. SYSTEM_SETTINGS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('system_settings')) {
        DB::statement("
            CREATE TABLE system_settings (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `key`       VARCHAR(100)    NOT NULL UNIQUE,
                value       TEXT            NULL,
                type        ENUM('string','integer','boolean','json','float') NOT NULL DEFAULT 'string',
                `group`     VARCHAR(50)     NOT NULL DEFAULT 'general',
                label       VARCHAR(200)    NULL,
                description TEXT            NULL,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL,
                INDEX idx_ss_group (`group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_04_100008_create_system_settings_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel system_settings dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel system_settings sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat system_settings: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 9. ORDERS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('orders')) {
        DB::statement("
            CREATE TABLE orders (
                id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                account_id              BIGINT UNSIGNED NULL,
                channel_id              BIGINT UNSIGNED NULL,
                warehouse_id            BIGINT UNSIGNED NULL,
                order_id                VARCHAR(100)    NOT NULL,
                platform                VARCHAR(30)     NOT NULL DEFAULT 'TIKTOK',
                order_status            VARCHAR(50)     NULL,
                buyer_user_id           VARCHAR(100)    NULL,
                buyer_name              VARCHAR(255)    NULL,
                buyer_phone             VARCHAR(50)     NULL,
                buyer_message           TEXT            NULL,
                shipping_type           VARCHAR(50)     NULL,
                shipping_provider       VARCHAR(100)    NULL,
                tracking_number         VARCHAR(100)    NULL,
                shipping_address        JSON            NULL,
                total_amount            DECIMAL(15,2)   NOT NULL DEFAULT 0,
                subtotal_amount         DECIMAL(15,2)   NOT NULL DEFAULT 0,
                shipping_fee            DECIMAL(15,2)   NOT NULL DEFAULT 0,
                seller_discount         DECIMAL(15,2)   NOT NULL DEFAULT 0,
                platform_discount       DECIMAL(15,2)   NOT NULL DEFAULT 0,
                currency                VARCHAR(10)     NOT NULL DEFAULT 'IDR',
                payment_method          VARCHAR(100)    NULL,
                payment_status          VARCHAR(50)     NULL,
                is_cod                  TINYINT(1)      NOT NULL DEFAULT 0,
                is_buyer_request_cancel TINYINT(1)      NOT NULL DEFAULT 0,
                is_on_hold_order        TINYINT(1)      NOT NULL DEFAULT 0,
                is_replacement_order    TINYINT(1)      NOT NULL DEFAULT 0,
                paid_at                 TIMESTAMP       NULL,
                shipped_at              TIMESTAMP       NULL,
                delivered_at            TIMESTAMP       NULL,
                completed_at            TIMESTAMP       NULL,
                cancelled_at            TIMESTAMP       NULL,
                cancel_reason           TEXT            NULL,
                tiktok_create_time      BIGINT          NULL,
                tiktok_update_time      BIGINT          NULL,
                is_synced_to_pos        TINYINT(1)      NOT NULL DEFAULT 0,
                synced_to_pos_at        TIMESTAMP       NULL,
                pos_order_id            VARCHAR(100)    NULL,
                raw_data                JSON            NULL,
                created_at              TIMESTAMP       NULL,
                updated_at              TIMESTAMP       NULL,
                UNIQUE KEY uq_orders_account_order (account_id, order_id),
                INDEX idx_orders_status (order_status),
                INDEX idx_orders_platform (platform),
                INDEX idx_orders_tiktok_create (tiktok_create_time),
                INDEX idx_orders_channel (channel_id),
                INDEX idx_orders_pos_sync (is_synced_to_pos)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_06_200001_create_orders_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel orders dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel orders sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat orders: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 10. ORDER_ITEMS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('order_items')) {
        DB::statement("
            CREATE TABLE order_items (
                id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                order_id          BIGINT UNSIGNED NOT NULL,
                product_id        VARCHAR(100)    NULL,
                product_name      VARCHAR(500)    NULL,
                sku_id            VARCHAR(100)    NULL,
                sku_name          VARCHAR(500)    NULL,
                seller_sku        VARCHAR(100)    NULL,
                quantity          INT UNSIGNED    NOT NULL DEFAULT 1,
                original_price    DECIMAL(15,2)   NOT NULL DEFAULT 0,
                sale_price        DECIMAL(15,2)   NOT NULL DEFAULT 0,
                platform_discount DECIMAL(15,2)   NOT NULL DEFAULT 0,
                seller_discount   DECIMAL(15,2)   NOT NULL DEFAULT 0,
                item_tax          DECIMAL(15,2)   NOT NULL DEFAULT 0,
                currency          VARCHAR(10)     NOT NULL DEFAULT 'IDR',
                product_image     VARCHAR(500)    NULL,
                created_at        TIMESTAMP       NULL,
                updated_at        TIMESTAMP       NULL,
                INDEX idx_oi_order_id (order_id),
                INDEX idx_oi_sku_id (sku_id),
                INDEX idx_oi_product_id (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_06_200002_create_order_items_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel order_items dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel order_items sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat order_items: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 11. PRODUCT_DETAILS TABLE
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasTable('product_details')) {
        DB::statement("
            CREATE TABLE product_details (
                id                            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                account_id                    BIGINT UNSIGNED NULL,
                product_id                    VARCHAR(100)    NOT NULL,
                platform                      VARCHAR(30)     NOT NULL DEFAULT 'TIKTOK',
                title                         VARCHAR(500)    NULL,
                description                   LONGTEXT        NULL,
                category_id                   VARCHAR(100)    NULL,
                category_name                 VARCHAR(255)    NULL,
                main_images                   JSON            NULL,
                video                         JSON            NULL,
                skus                          JSON            NULL,
                product_status                VARCHAR(50)     NULL,
                product_attributes            JSON            NULL,
                size_chart                    JSON            NULL,
                brand_id                      VARCHAR(100)    NULL,
                brand_name                    VARCHAR(255)    NULL,
                package_weight                DECIMAL(10,3)   NULL,
                package_length                DECIMAL(10,2)   NULL,
                package_width                 DECIMAL(10,2)   NULL,
                package_height                DECIMAL(10,2)   NULL,
                package_dimensions_unit       VARCHAR(10)     NULL,
                product_certifications        JSON            NULL,
                delivery_options              JSON            NULL,
                integrated_platform_statuses  JSON            NULL,
                tiktok_create_time            BIGINT          NULL,
                tiktok_update_time            BIGINT          NULL,
                raw_data                      JSON            NULL,
                created_at                    TIMESTAMP       NULL,
                updated_at                    TIMESTAMP       NULL,
                UNIQUE KEY uq_pd_account_product (account_id, product_id),
                INDEX idx_pd_product_id (product_id),
                INDEX idx_pd_platform (platform),
                INDEX idx_pd_status (product_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $recordMigration('2026_04_06_200003_create_product_details_table');
        $results[] = ['status' => '✅', 'msg' => 'Tabel product_details dibuat'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Tabel product_details sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal buat product_details: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 12. ALTER users — add role_id FK
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasColumn('users', 'role_id')) {
        DB::statement("ALTER TABLE users ADD COLUMN role_id BIGINT UNSIGNED NULL AFTER role");
        $results[] = ['status' => '✅', 'msg' => 'Kolom users.role_id ditambahkan'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom users.role_id sudah ada'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal tambah users.role_id: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 13. ALTER account_shop_tiktok — add channel_id, warehouse_id, user_id
// ═══════════════════════════════════════════════════════════════════════════════
$astColumns = [
    'channel_id'   => "ADD COLUMN channel_id BIGINT UNSIGNED NULL AFTER id",
    'user_id'      => "ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER channel_id",
    'warehouse_id' => "ADD COLUMN warehouse_id BIGINT UNSIGNED NULL AFTER user_id",
];
foreach ($astColumns as $col => $sql) {
    try {
        if (!Schema::hasColumn('account_shop_tiktok', $col)) {
            DB::statement("ALTER TABLE account_shop_tiktok {$sql}");
            $results[] = ['status' => '✅', 'msg' => "Kolom account_shop_tiktok.{$col} ditambahkan"];
        } else {
            $results[] = ['status' => 'ℹ️', 'msg' => "Kolom account_shop_tiktok.{$col} sudah ada"];
        }
    } catch (\Throwable $e) {
        $results[] = ['status' => '❌', 'msg' => "Gagal tambah account_shop_tiktok.{$col}: " . $e->getMessage()];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 14. ALTER produk_saya — add channel_id
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (!Schema::hasColumn('produk_saya', 'channel_id')) {
        DB::statement("ALTER TABLE produk_saya ADD COLUMN channel_id BIGINT UNSIGNED NULL AFTER account_id");
        $results[] = ['status' => '✅', 'msg' => 'Kolom produk_saya.channel_id ditambahkan'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom produk_saya.channel_id sudah ada'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal tambah produk_saya.channel_id: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 15. ALTER account_shop_tiktok — expire columns to DATETIME
// ═══════════════════════════════════════════════════════════════════════════════
try {
    // Check current type — if it's not DATETIME, alter it
    $colType = DB::selectOne("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'account_shop_tiktok' AND COLUMN_NAME = 'access_token_expire_in'");
    if ($colType && $colType->DATA_TYPE !== 'datetime') {
        DB::statement("ALTER TABLE account_shop_tiktok MODIFY COLUMN access_token_expire_in DATETIME NULL");
        DB::statement("ALTER TABLE account_shop_tiktok MODIFY COLUMN refresh_token_expire_in DATETIME NULL");
        $recordMigration('2026_04_04_000003_alter_account_shop_tiktok_expire_columns');
        $results[] = ['status' => '✅', 'msg' => 'Kolom expire di account_shop_tiktok diubah ke DATETIME'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom expire sudah DATETIME — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal alter expire columns: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// 16. SEED: Roles + Permissions + Marketplace Channels + Warehouse + Settings
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (DB::table('roles')->count() === 0) {
        $now = now();
        $roles = [
            ['name' => 'super_admin',  'label' => 'Super Admin',  'level' => 100],
            ['name' => 'admin',        'label' => 'Admin',        'level' => 80],
            ['name' => 'manager',      'label' => 'Manager',      'level' => 60],
            ['name' => 'staff_admin',  'label' => 'Staff Admin',  'level' => 40],
            ['name' => 'finance',      'label' => 'Finance',      'level' => 35],
            ['name' => 'cs',           'label' => 'Customer Service', 'level' => 25],
            ['name' => 'operator',     'label' => 'Operator',     'level' => 20],
        ];
        foreach ($roles as $r) {
            DB::table('roles')->insert(array_merge($r, ['created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($roles) . ' roles'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Roles sudah terisi — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed roles: ' . $e->getMessage()];
}

try {
    if (DB::table('permissions')->count() === 0) {
        $now = now();
        $permissions = [
            // dashboard
            ['name' => 'dashboard.view', 'label' => 'Lihat Dashboard', 'group' => 'dashboard'],
            // users
            ['name' => 'users.view', 'label' => 'Lihat Pengguna', 'group' => 'users'],
            ['name' => 'users.create', 'label' => 'Tambah Pengguna', 'group' => 'users'],
            ['name' => 'users.edit', 'label' => 'Edit Pengguna', 'group' => 'users'],
            ['name' => 'users.delete', 'label' => 'Hapus Pengguna', 'group' => 'users'],
            // products
            ['name' => 'products.view', 'label' => 'Lihat Produk', 'group' => 'products'],
            ['name' => 'products.create', 'label' => 'Tambah Produk', 'group' => 'products'],
            ['name' => 'products.edit', 'label' => 'Edit Produk', 'group' => 'products'],
            ['name' => 'products.delete', 'label' => 'Hapus Produk', 'group' => 'products'],
            ['name' => 'products.sync', 'label' => 'Sinkron Produk', 'group' => 'products'],
            // orders
            ['name' => 'orders.view', 'label' => 'Lihat Pesanan', 'group' => 'orders'],
            ['name' => 'orders.process', 'label' => 'Proses Pesanan', 'group' => 'orders'],
            ['name' => 'orders.cancel', 'label' => 'Batalkan Pesanan', 'group' => 'orders'],
            ['name' => 'orders.sync', 'label' => 'Sinkron Pesanan', 'group' => 'orders'],
            // stock
            ['name' => 'stock.view', 'label' => 'Lihat Stok', 'group' => 'stock'],
            ['name' => 'stock.sync', 'label' => 'Sinkron Stok', 'group' => 'stock'],
            ['name' => 'stock.manage', 'label' => 'Kelola Stok', 'group' => 'stock'],
            // channels
            ['name' => 'channels.view', 'label' => 'Lihat Channel', 'group' => 'channels'],
            ['name' => 'channels.manage', 'label' => 'Kelola Channel', 'group' => 'channels'],
            ['name' => 'channels.connect', 'label' => 'Hubungkan Channel', 'group' => 'channels'],
            ['name' => 'channels.disconnect', 'label' => 'Putuskan Channel', 'group' => 'channels'],
            // warehouses
            ['name' => 'warehouses.view', 'label' => 'Lihat Gudang', 'group' => 'warehouses'],
            ['name' => 'warehouses.manage', 'label' => 'Kelola Gudang', 'group' => 'warehouses'],
            // reports
            ['name' => 'reports.view', 'label' => 'Lihat Laporan', 'group' => 'reports'],
            ['name' => 'reports.export', 'label' => 'Export Laporan', 'group' => 'reports'],
            // settings
            ['name' => 'settings.view', 'label' => 'Lihat Pengaturan', 'group' => 'settings'],
            ['name' => 'settings.edit', 'label' => 'Edit Pengaturan', 'group' => 'settings'],
            ['name' => 'activity_logs.view', 'label' => 'Lihat Log Aktivitas', 'group' => 'settings'],
            ['name' => 'activity_logs.clear', 'label' => 'Hapus Log Aktivitas', 'group' => 'settings'],
        ];
        foreach ($permissions as $p) {
            DB::table('permissions')->insert(array_merge($p, ['created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($permissions) . ' permissions'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Permissions sudah terisi — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed permissions: ' . $e->getMessage()];
}

// Seed Marketplace Channels
try {
    if (DB::table('marketplace_channels')->count() === 0) {
        $now = now();
        $channels = [
            ['code' => 'TIKTOK',     'name' => 'TikTok Shop',     'color' => '#000000', 'sort_order' => 1],
            ['code' => 'SHOPEE',     'name' => 'Shopee',          'color' => '#EE4D2D', 'sort_order' => 2],
            ['code' => 'TOKOPEDIA',  'name' => 'Tokopedia',       'color' => '#42B549', 'sort_order' => 3],
            ['code' => 'LAZADA',     'name' => 'Lazada',          'color' => '#0F146D', 'sort_order' => 4],
            ['code' => 'BUKALAPAK',  'name' => 'Bukalapak',       'color' => '#E31E52', 'sort_order' => 5],
            ['code' => 'BLIBLI',     'name' => 'Blibli',          'color' => '#0095DA', 'sort_order' => 6],
            ['code' => 'WEBSITE',    'name' => 'Website / WooCommerce', 'color' => '#96588A', 'sort_order' => 7],
            ['code' => 'OFFLINE',    'name' => 'Offline / POS',   'color' => '#6b7280', 'sort_order' => 8],
        ];
        foreach ($channels as $c) {
            DB::table('marketplace_channels')->insert(array_merge($c, ['is_active' => 1, 'created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($channels) . ' marketplace channels'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Marketplace channels sudah terisi — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed channels: ' . $e->getMessage()];
}

// Seed Default Warehouse
try {
    if (DB::table('warehouses')->count() === 0) {
        DB::table('warehouses')->insert([
            'name'       => 'Gudang Utama',
            'code'       => 'WH-MAIN',
            'is_default' => 1,
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $results[] = ['status' => '✅', 'msg' => 'Seeded default warehouse'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Warehouses sudah terisi — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed warehouse: ' . $e->getMessage()];
}

// Seed System Settings
try {
    if (DB::table('system_settings')->count() === 0) {
        $now = now();
        $settings = [
            ['key' => 'app_name', 'value' => 'Kios Q Omni-Channel', 'type' => 'string', 'group' => 'general', 'label' => 'Nama Aplikasi'],
            ['key' => 'default_currency', 'value' => 'IDR', 'type' => 'string', 'group' => 'general', 'label' => 'Mata Uang Default'],
            ['key' => 'sync_interval_minutes', 'value' => '30', 'type' => 'integer', 'group' => 'sync', 'label' => 'Interval Sinkronisasi (menit)'],
            ['key' => 'auto_sync_stock', 'value' => '1', 'type' => 'boolean', 'group' => 'sync', 'label' => 'Auto Sinkron Stok'],
            ['key' => 'auto_sync_orders', 'value' => '1', 'type' => 'boolean', 'group' => 'sync', 'label' => 'Auto Sinkron Pesanan'],
            ['key' => 'stock_buffer', 'value' => '0', 'type' => 'integer', 'group' => 'stock', 'label' => 'Buffer Stok'],
            ['key' => 'low_stock_threshold', 'value' => '5', 'type' => 'integer', 'group' => 'stock', 'label' => 'Batas Stok Rendah'],
        ];
        foreach ($settings as $s) {
            DB::table('system_settings')->insert(array_merge($s, ['created_at' => $now, 'updated_at' => $now]));
        }
        $results[] = ['status' => '✅', 'msg' => 'Seeded ' . count($settings) . ' system settings'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'System settings sudah terisi — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal seed settings: ' . $e->getMessage()];
}

// Map existing users.role → role_id
try {
    $usersWithoutRoleId = DB::table('users')->whereNull('role_id')->count();
    if ($usersWithoutRoleId > 0 && Schema::hasColumn('users', 'role_id') && Schema::hasTable('roles')) {
        $roleMap = DB::table('roles')->pluck('id', 'name');
        $updated = 0;
        foreach ($roleMap as $name => $id) {
            $count = DB::table('users')->where('role', $name)->whereNull('role_id')->update(['role_id' => $id]);
            $updated += $count;
        }
        $results[] = ['status' => '✅', 'msg' => "Mapped role_id for {$updated} users"];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Semua users sudah punya role_id'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal map role_id: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// ALTER: orders — tambah buyer_email
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'buyer_email')) {
        DB::statement("ALTER TABLE orders ADD COLUMN buyer_email VARCHAR(255) NULL AFTER buyer_phone");
        $recordMigration('2026_04_13_000001_add_buyer_email_to_orders_table');
        $results[] = ['status' => '✅', 'msg' => 'Kolom orders.buyer_email ditambahkan'];
    } else {
        $results[] = ['status' => 'ℹ️', 'msg' => 'Kolom orders.buyer_email sudah ada — dilewati'];
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal tambah orders.buyer_email: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// ALTER: order_items — tambah tiktok_line_item_id, currency, item_status, is_gift
// ═══════════════════════════════════════════════════════════════════════════════
try {
    if (Schema::hasTable('order_items')) {
        $added = [];

        if (!Schema::hasColumn('order_items', 'tiktok_line_item_id')) {
            DB::statement("ALTER TABLE order_items ADD COLUMN tiktok_line_item_id VARCHAR(50) NULL AFTER tiktok_order_id");
            $added[] = 'tiktok_line_item_id';
        }
        if (!Schema::hasColumn('order_items', 'subtotal')) {
            DB::statement("ALTER TABLE order_items ADD COLUMN subtotal DECIMAL(15,2) NOT NULL DEFAULT 0");
            $added[] = 'subtotal';
        }
        if (!Schema::hasColumn('order_items', 'currency')) {
            DB::statement("ALTER TABLE order_items ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'IDR'");
            $added[] = 'currency';
        }
        if (!Schema::hasColumn('order_items', 'item_status')) {
            DB::statement("ALTER TABLE order_items ADD COLUMN item_status VARCHAR(50) NULL");
            $added[] = 'item_status';
        }
        if (!Schema::hasColumn('order_items', 'is_gift')) {
            DB::statement("ALTER TABLE order_items ADD COLUMN is_gift TINYINT(1) NOT NULL DEFAULT 0");
            $added[] = 'is_gift';
        }

        if (!empty($added)) {
            $recordMigration('2026_04_13_000002_add_missing_columns_to_order_items_table');
            $results[] = ['status' => '✅', 'msg' => 'Kolom order_items ditambahkan: ' . implode(', ', $added)];
        } else {
            $results[] = ['status' => 'ℹ️', 'msg' => 'Semua kolom order_items sudah ada — dilewati'];
        }
    }
} catch (\Throwable $e) {
    $results[] = ['status' => '❌', 'msg' => 'Gagal tambah kolom order_items: ' . $e->getMessage()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// VERIFIKASI
// ═══════════════════════════════════════════════════════════════════════════════
$checks = [
    'roles'               => Schema::hasTable('roles'),
    'permissions'          => Schema::hasTable('permissions'),
    'role_permissions'     => Schema::hasTable('role_permissions'),
    'marketplace_channels' => Schema::hasTable('marketplace_channels'),
    'warehouses'           => Schema::hasTable('warehouses'),
    'channel_accounts'     => Schema::hasTable('channel_accounts'),
    'activity_logs'        => Schema::hasTable('activity_logs'),
    'system_settings'      => Schema::hasTable('system_settings'),
    'orders'               => Schema::hasTable('orders'),
    'order_items'          => Schema::hasTable('order_items'),
    'product_details'      => Schema::hasTable('product_details'),
    'col: users.role_id'   => Schema::hasColumn('users', 'role_id'),
    'col: ast.channel_id'  => Schema::hasColumn('account_shop_tiktok', 'channel_id'),
    'col: ast.warehouse_id' => Schema::hasColumn('account_shop_tiktok', 'warehouse_id'),
    'col: produk.channel_id' => Schema::hasColumn('produk_saya', 'channel_id'),
    // kolom baru v2 patch
    'col: orders.buyer_email'              => Schema::hasColumn('orders', 'buyer_email'),
    'col: order_items.subtotal'            => Schema::hasColumn('order_items', 'subtotal'),
    'col: order_items.tiktok_line_item_id' => Schema::hasColumn('order_items', 'tiktok_line_item_id'),
    'col: order_items.currency'            => Schema::hasColumn('order_items', 'currency'),
    'col: order_items.item_status'         => Schema::hasColumn('order_items', 'item_status'),
    'col: order_items.is_gift'             => Schema::hasColumn('order_items', 'is_gift'),
    // existing
    'jobs'                 => Schema::hasTable('jobs'),
    'sessions'             => Schema::hasTable('sessions'),
];

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Migration v2 — Omni-Channel</title>
    <style>
        body {
            font-family: 'Segoe UI', monospace;
            background: #0f172a;
            color: #e2e8f0;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        h2 {
            color: #38bdf8;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
        }

        h3 {
            color: #94a3b8;
            margin-top: 24px;
        }

        .ok {
            color: #4ade80;
            padding: 4px 0;
        }

        .err {
            color: #f87171;
            padding: 4px 0;
        }

        .inf {
            color: #94a3b8;
            padding: 4px 0;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #334155;
            padding: 8px 14px;
        }

        th {
            background: #1e293b;
            color: #38bdf8;
            font-weight: 600;
        }

        tr:hover {
            background: #1e293b;
        }

        .warn {
            background: #422006;
            color: #fbbf24;
            padding: 12px 16px;
            border-left: 4px solid #f59e0b;
            margin: 16px 0;
            border-radius: 6px;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .badge-ok {
            background: #166534;
            color: #4ade80;
        }

        .badge-err {
            background: #7f1d1d;
            color: #fca5a5;
        }
    </style>
</head>

<body>
    <h2>🚀 Migration v2 — Kios Q Omni-Channel Platform</h2>
    <p style="color:#64748b">Executed at: <?= date('Y-m-d H:i:s') ?></p>

    <h3>📋 Hasil Eksekusi (<?= count($results) ?> operasi)</h3>
    <?php foreach ($results as $r): ?>
        <div class="<?= $r['status'] === '✅' ? 'ok' : ($r['status'] === '❌' ? 'err' : 'inf') ?>">
            <?= htmlspecialchars($r['status'] . ' ' . $r['msg']) ?>
        </div>
    <?php endforeach; ?>

    <h3>🔍 Verifikasi Tabel & Kolom</h3>
    <table>
        <tr>
            <th>Tabel / Kolom</th>
            <th>Status</th>
        </tr>
        <?php foreach ($checks as $name => $exists): ?>
            <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><span class="badge <?= $exists ? 'badge-ok' : 'badge-err' ?>"><?= $exists ? '✅ Ada' : '❌ TIDAK ADA' ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="warn">
        ⚠️ Setelah semua berstatus ✅, <strong>HAPUS file ini</strong> dari server demi keamanan.
    </div>
</body>

</html>