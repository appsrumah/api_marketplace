<?php

/**
 * migrate-v2.php — Migration runner via browser/CLI untuk TikTok CS Webhook
 * 
 * ⚠️ PERINGATAN KEAMANAN:
 *   - HAPUS file ini setelah migration berhasil dijalankan!
 *   - Jangan biarkan file ini accessible di production tanpa proteksi
 * 
 * Akses via browser: https://yourdomain.com/migrate-v2.php?secret=MIGRATION_SECRET_KEY
 * Akses via CLI:     php public/migrate-v2.php
 */

// ─────────────────────────────────────────────────────────────────────────────
// KONFIGURASI — Sesuaikan sebelum upload
// ─────────────────────────────────────────────────────────────────────────────
define('MIGRATION_SECRET', getenv('MIGRATION_SECRET') ?: 'GANTI_INI_DENGAN_SECRET_ANDA');
define('DB_HOST',     getenv('DB_HOST')     ?: '127.0.0.1');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_NAME',     getenv('DB_DATABASE') ?: 'your_db_name');
define('DB_USER',     getenv('DB_USERNAME') ?: 'your_db_user');
define('DB_PASS',     getenv('DB_PASSWORD') ?: 'your_db_password');
define('DB_CHARSET',  'utf8mb4');

// ─────────────────────────────────────────────────────────────────────────────
// PROTEKSI AKSES
// ─────────────────────────────────────────────────────────────────────────────
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Browser: wajib pakai secret
    if (!isset($_GET['secret']) || $_GET['secret'] !== MIGRATION_SECRET) {
        http_response_code(403);
        die('403 Forbidden — Akses ditolak. Sertakan ?secret=YOUR_SECRET');
    }
    header('Content-Type: text/html; charset=utf-8');
} else {
    echo "=== TikTok CS Webhook Migration v2 ===\n";
    echo "Mode: CLI\n\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// HTML HEADER (browser only)
// ─────────────────────────────────────────────────────────────────────────────
if (!$isCli): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok CS Webhook — Migration v2</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', monospace; background: #0f0f0f; color: #e0e0e0; padding: 20px; }
        h1 { color: #ff4757; margin-bottom: 4px; font-size: 1.4rem; }
        .subtitle { color: #888; font-size: 0.85rem; margin-bottom: 20px; }
        .card { background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .card h2 { font-size: 1rem; color: #ff6b81; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #333; }
        .log { font-family: monospace; font-size: 0.82rem; line-height: 1.7; }
        .ok    { color: #2ed573; }
        .skip  { color: #ffa502; }
        .error { color: #ff4757; }
        .info  { color: #1e90ff; }
        .warn  { color: #eccc68; }
        .sql   { color: #a29bfe; font-size: 0.75rem; display: block; margin-left: 20px; opacity: 0.8; }
        .summary { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600; }
        .badge-ok    { background: #1e3a2f; color: #2ed573; border: 1px solid #2ed573; }
        .badge-skip  { background: #3a2e1e; color: #ffa502; border: 1px solid #ffa502; }
        .badge-error { background: #3a1e1e; color: #ff4757; border: 1px solid #ff4757; }
        .warning-box { background: #3a2e00; border: 1px solid #ffa502; border-radius: 8px; padding: 12px 16px; margin-top: 16px; color: #ffa502; font-size: 0.85rem; }
        .warning-box strong { display: block; margin-bottom: 4px; font-size: 0.95rem; }
        pre { background: #111; border: 1px solid #333; border-radius: 6px; padding: 12px; overflow-x: auto; font-size: 0.8rem; color: #a29bfe; margin-top: 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🗃️ TikTok CS Webhook — Migration v2</h1>
    <p class="subtitle">Membuat tabel untuk integrasi Customer Service Webhook TikTok Shop</p>
<?php endif;

// ─────────────────────────────────────────────────────────────────────────────
// KONEKSI DATABASE
// ─────────────────────────────────────────────────────────────────────────────
function out(string $message, string $type = 'info', bool $isCli = false): void
{
    if ($isCli) {
        $prefix = match($type) {
            'ok'    => '✓ ',
            'skip'  => '⟳ ',
            'error' => '✗ ',
            'warn'  => '⚠ ',
            'sql'   => '  SQL: ',
            default => '→ ',
        };
        echo $prefix . strip_tags($message) . "\n";
    } else {
        $class = match($type) {
            'ok', 'skip', 'error', 'warn', 'sql', 'info' => $type,
            default => 'info',
        };
        echo "<div class=\"{$class}\">" . htmlspecialchars($message) . "</div>";
    }
    flush();
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    out('Koneksi database berhasil ke: ' . DB_NAME, 'ok', $isCli);
} catch (PDOException $e) {
    out('GAGAL koneksi database: ' . $e->getMessage(), 'error', $isCli);
    if (!$isCli) echo '</div></body></html>';
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER: cek kolom ada atau tidak
// ─────────────────────────────────────────────────────────────────────────────
function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :col");
    $stmt->execute([':col' => $column]);
    return $stmt->rowCount() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tbl");
    $stmt->execute([':tbl' => $table]);
    return $stmt->rowCount() > 0;
}

function indexExists(PDO $pdo, string $table, string $indexName): bool
{
    $stmt = $pdo->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = :idx");
    $stmt->execute([':idx' => $indexName]);
    return $stmt->rowCount() > 0;
}

function runSql(PDO $pdo, string $sql, string $desc, bool $isCli): void
{
    try {
        $pdo->exec($sql);
        out($desc, 'ok', $isCli);
    } catch (PDOException $e) {
        out("GAGAL: {$desc} — " . $e->getMessage(), 'error', $isCli);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// STATS
// ─────────────────────────────────────────────────────────────────────────────
$stats = ['created' => 0, 'altered' => 0, 'skipped' => 0, 'errors' => 0];

// ─────────────────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════
// MIGRATION 1: tiktok_conversations
// ═══════════════════════════════════════════════════════════════
// ─────────────────────────────────────────────────────────────────────────────
if (!$isCli) echo '<div class="card"><h2>📋 Tabel: tiktok_conversations</h2><div class="log">';
else echo "\n--- [1/3] tiktok_conversations ---\n";

if (!tableExists($pdo, 'tiktok_conversations')) {
    $sql = <<<SQL
CREATE TABLE `tiktok_conversations` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id`          BIGINT UNSIGNED NOT NULL COMMENT 'FK ke account_shop_tiktoks.id',
    `conversation_id`     VARCHAR(128)    NOT NULL COMMENT 'ID percakapan dari TikTok',
    `buyer_user_id`       VARCHAR(128)    NULL      COMMENT 'UID buyer di TikTok',
    `buyer_nickname`      VARCHAR(255)    NULL      COMMENT 'Nama buyer di TikTok',
    `buyer_avatar`        VARCHAR(500)    NULL      COMMENT 'URL avatar buyer',
    `shop_id`             VARCHAR(128)    NULL      COMMENT 'Shop ID dari TikTok',
    `status`              VARCHAR(32)     NOT NULL DEFAULT 'open'
                          COMMENT 'open | closed | pending',
    `unread_count`        INT UNSIGNED    NOT NULL DEFAULT 0,
    `latest_message`      TEXT            NULL      COMMENT 'Preview pesan terakhir',
    `last_message_at`     TIMESTAMP       NULL,
    `assigned_agent_id`   BIGINT UNSIGNED NULL      COMMENT 'FK ke users.id (agent CS)',
    `tiktok_created_at`   TIMESTAMP       NULL      COMMENT 'Waktu buat dari TikTok',
    `tiktok_updated_at`   TIMESTAMP       NULL      COMMENT 'Waktu update dari TikTok',
    `extra`               JSON            NULL      COMMENT 'Metadata tambahan',
    `created_at`          TIMESTAMP       NULL,
    `updated_at`          TIMESTAMP       NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_conv_account` (`conversation_id`, `account_id`),
    KEY `idx_conv_account`       (`account_id`),
    KEY `idx_conv_status`        (`status`),
    KEY `idx_conv_buyer`         (`buyer_user_id`),
    KEY `idx_conv_last_msg`      (`last_message_at`),
    KEY `idx_conv_agent`         (`assigned_agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    try {
        $pdo->exec($sql);
        out('CREATE TABLE tiktok_conversations — OK', 'ok', $isCli);
        $stats['created']++;
    } catch (PDOException $e) {
        out('GAGAL buat tiktok_conversations: ' . $e->getMessage(), 'error', $isCli);
        $stats['errors']++;
    }
} else {
    out('Tabel tiktok_conversations sudah ada — cek kolom...', 'skip', $isCli);
    $stats['skipped']++;

    // Tambah kolom baru jika belum ada
    $alterColumns = [
        'buyer_nickname'   => "ALTER TABLE `tiktok_conversations` ADD COLUMN `buyer_nickname` VARCHAR(255) NULL AFTER `buyer_user_id`",
        'buyer_avatar'     => "ALTER TABLE `tiktok_conversations` ADD COLUMN `buyer_avatar` VARCHAR(500) NULL AFTER `buyer_nickname`",
        'shop_id'          => "ALTER TABLE `tiktok_conversations` ADD COLUMN `shop_id` VARCHAR(128) NULL AFTER `buyer_avatar`",
        'unread_count'     => "ALTER TABLE `tiktok_conversations` ADD COLUMN `unread_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`",
        'latest_message'   => "ALTER TABLE `tiktok_conversations` ADD COLUMN `latest_message` TEXT NULL AFTER `unread_count`",
        'assigned_agent_id'=> "ALTER TABLE `tiktok_conversations` ADD COLUMN `assigned_agent_id` BIGINT UNSIGNED NULL AFTER `last_message_at`",
        'tiktok_updated_at'=> "ALTER TABLE `tiktok_conversations` ADD COLUMN `tiktok_updated_at` TIMESTAMP NULL AFTER `tiktok_created_at`",
        'extra'            => "ALTER TABLE `tiktok_conversations` ADD COLUMN `extra` JSON NULL AFTER `tiktok_updated_at`",
    ];

    foreach ($alterColumns as $col => $alterSql) {
        if (!columnExists($pdo, 'tiktok_conversations', $col)) {
            try {
                $pdo->exec($alterSql);
                out("  + Kolom `{$col}` ditambahkan", 'ok', $isCli);
                $stats['altered']++;
            } catch (PDOException $e) {
                out("  ✗ Gagal tambah kolom `{$col}`: " . $e->getMessage(), 'error', $isCli);
                $stats['errors']++;
            }
        } else {
            out("  ⟳ Kolom `{$col}` sudah ada", 'skip', $isCli);
        }
    }
}

if (!$isCli) echo '</div></div>';

// ─────────────────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════
// MIGRATION 2: tiktok_messages
// ═══════════════════════════════════════════════════════════════
// ─────────────────────────────────────────────────────────────────────────────
if (!$isCli) echo '<div class="card"><h2>💬 Tabel: tiktok_messages</h2><div class="log">';
else echo "\n--- [2/3] tiktok_messages ---\n";

if (!tableExists($pdo, 'tiktok_messages')) {
    $sql = <<<SQL
CREATE TABLE `tiktok_messages` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id`     BIGINT UNSIGNED NOT NULL COMMENT 'FK ke tiktok_conversations.id (local)',
    `tiktok_conv_id`      VARCHAR(128)    NOT NULL COMMENT 'ID percakapan dari TikTok',
    `message_id`          VARCHAR(128)    NOT NULL COMMENT 'ID pesan dari TikTok',
    `sender_type`         VARCHAR(32)     NOT NULL
                          COMMENT 'BUYER | CUSTOMER_SERVICE | SHOP | SYSTEM | ROBOT',
    `sender_id`           VARCHAR(128)    NULL      COMMENT 'UID pengirim',
    `sender_name`         VARCHAR(255)    NULL      COMMENT 'Nama pengirim',
    `content_type`        VARCHAR(32)     NOT NULL  DEFAULT 'text'
                          COMMENT 'text | image | video | product_card | order | sticker | file | system_text',
    `content`             LONGTEXT        NULL      COMMENT 'Isi pesan (text atau JSON encoded)',
    `is_read`             TINYINT(1)      NOT NULL  DEFAULT 0,
    `is_outbound`         TINYINT(1)      NOT NULL  DEFAULT 0 COMMENT '1 = dikirim oleh agent kita',
    `tiktok_created_at`   TIMESTAMP       NULL      COMMENT 'Waktu buat dari TikTok (server)',
    `created_at`          TIMESTAMP       NULL,
    `updated_at`          TIMESTAMP       NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_msg_id` (`message_id`),
    KEY `idx_msg_conv`          (`conversation_id`),
    KEY `idx_msg_tiktok_conv`   (`tiktok_conv_id`),
    KEY `idx_msg_sender`        (`sender_type`),
    KEY `idx_msg_created`       (`tiktok_created_at`),
    KEY `idx_msg_is_read`       (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    try {
        $pdo->exec($sql);
        out('CREATE TABLE tiktok_messages — OK', 'ok', $isCli);
        $stats['created']++;
    } catch (PDOException $e) {
        out('GAGAL buat tiktok_messages: ' . $e->getMessage(), 'error', $isCli);
        $stats['errors']++;
    }
} else {
    out('Tabel tiktok_messages sudah ada — cek kolom...', 'skip', $isCli);
    $stats['skipped']++;

    $alterColumns = [
        'tiktok_conv_id' => "ALTER TABLE `tiktok_messages` ADD COLUMN `tiktok_conv_id` VARCHAR(128) NOT NULL DEFAULT '' AFTER `conversation_id`",
        'sender_id'      => "ALTER TABLE `tiktok_messages` ADD COLUMN `sender_id` VARCHAR(128) NULL AFTER `sender_type`",
        'sender_name'    => "ALTER TABLE `tiktok_messages` ADD COLUMN `sender_name` VARCHAR(255) NULL AFTER `sender_id`",
        'is_read'        => "ALTER TABLE `tiktok_messages` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `content`",
        'is_outbound'    => "ALTER TABLE `tiktok_messages` ADD COLUMN `is_outbound` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`",
    ];

    foreach ($alterColumns as $col => $alterSql) {
        if (!columnExists($pdo, 'tiktok_messages', $col)) {
            try {
                $pdo->exec($alterSql);
                out("  + Kolom `{$col}` ditambahkan", 'ok', $isCli);
                $stats['altered']++;
            } catch (PDOException $e) {
                out("  ✗ Gagal tambah kolom `{$col}`: " . $e->getMessage(), 'error', $isCli);
                $stats['errors']++;
            }
        } else {
            out("  ⟳ Kolom `{$col}` sudah ada", 'skip', $isCli);
        }
    }
}

if (!$isCli) echo '</div></div>';

// ─────────────────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════
// MIGRATION 3: tiktok_webhook_logs
// ═══════════════════════════════════════════════════════════════
// ─────────────────────────────────────────────────────────────────────────────
if (!$isCli) echo '<div class="card"><h2>📜 Tabel: tiktok_webhook_logs</h2><div class="log">';
else echo "\n--- [3/3] tiktok_webhook_logs ---\n";

if (!tableExists($pdo, 'tiktok_webhook_logs')) {
    $sql = <<<SQL
CREATE TABLE `tiktok_webhook_logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type`     VARCHAR(64)     NOT NULL  COMMENT '13=NEW_CONVERSATION, 14=NEW_MESSAGE',
    `shop_id`        VARCHAR(128)    NULL       COMMENT 'Shop ID dari payload TikTok',
    `raw_payload`    LONGTEXT        NULL       COMMENT 'Raw JSON payload dari TikTok',
    `process_status` VARCHAR(32)     NOT NULL   DEFAULT 'pending'
                     COMMENT 'pending | completed | failed',
    `error_message`  TEXT            NULL       COMMENT 'Pesan error jika gagal',
    `processed_at`   TIMESTAMP       NULL       COMMENT 'Waktu selesai diproses',
    `ip_address`     VARCHAR(45)     NULL       COMMENT 'IP address pengirim',
    `created_at`     TIMESTAMP       NULL,
    `updated_at`     TIMESTAMP       NULL,

    PRIMARY KEY (`id`),
    KEY `idx_wlog_event`    (`event_type`),
    KEY `idx_wlog_status`   (`process_status`),
    KEY `idx_wlog_shop`     (`shop_id`),
    KEY `idx_wlog_created`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    try {
        $pdo->exec($sql);
        out('CREATE TABLE tiktok_webhook_logs — OK', 'ok', $isCli);
        $stats['created']++;
    } catch (PDOException $e) {
        out('GAGAL buat tiktok_webhook_logs: ' . $e->getMessage(), 'error', $isCli);
        $stats['errors']++;
    }
} else {
    out('Tabel tiktok_webhook_logs sudah ada — cek kolom...', 'skip', $isCli);
    $stats['skipped']++;

    $alterColumns = [
        'shop_id'       => "ALTER TABLE `tiktok_webhook_logs` ADD COLUMN `shop_id` VARCHAR(128) NULL AFTER `event_type`",
        'error_message' => "ALTER TABLE `tiktok_webhook_logs` ADD COLUMN `error_message` TEXT NULL AFTER `process_status`",
        'ip_address'    => "ALTER TABLE `tiktok_webhook_logs` ADD COLUMN `ip_address` VARCHAR(45) NULL AFTER `processed_at`",
    ];

    foreach ($alterColumns as $col => $alterSql) {
        if (!columnExists($pdo, 'tiktok_webhook_logs', $col)) {
            try {
                $pdo->exec($alterSql);
                out("  + Kolom `{$col}` ditambahkan", 'ok', $isCli);
                $stats['altered']++;
            } catch (PDOException $e) {
                out("  ✗ Gagal tambah kolom `{$col}`: " . $e->getMessage(), 'error', $isCli);
                $stats['errors']++;
            }
        } else {
            out("  ⟳ Kolom `{$col}` sudah ada", 'skip', $isCli);
        }
    }
}

if (!$isCli) echo '</div></div>';

// ─────────────────────────────────────────────────────────────────────────────
// VERIFIKASI AKHIR — Cek semua tabel terbuat
// ─────────────────────────────────────────────────────────────────────────────
if (!$isCli) echo '<div class="card"><h2>✅ Verifikasi Tabel</h2><div class="log">';
else echo "\n--- Verifikasi ---\n";

$requiredTables = ['tiktok_conversations', 'tiktok_messages', 'tiktok_webhook_logs'];
$allOk = true;
foreach ($requiredTables as $tbl) {
    if (tableExists($pdo, $tbl)) {
        // Hitung jumlah kolom
        $colStmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tbl}'");
        $colCount = $colStmt->fetch()['cnt'];
        out("✓ {$tbl} ({$colCount} kolom)", 'ok', $isCli);
    } else {
        out("✗ {$tbl} TIDAK ADA!", 'error', $isCli);
        $allOk = false;
        $stats['errors']++;
    }
}

if (!$isCli) echo '</div></div>';

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY OUTPUT
// ─────────────────────────────────────────────────────────────────────────────
if ($isCli) {
    echo "\n=== HASIL ===\n";
    echo "✓ Tabel dibuat baru : {$stats['created']}\n";
    echo "✓ Kolom ditambahkan : {$stats['altered']}\n";
    echo "⟳ Dilewati (ada)   : {$stats['skipped']}\n";
    echo "✗ Error            : {$stats['errors']}\n";
    if ($allOk) {
        echo "\n✅ Semua tabel siap!\n";
        echo "⚠️  PENTING: Hapus file public/migrate-v2.php setelah ini!\n";
    } else {
        echo "\n❌ Ada error! Cek output di atas.\n";
    }
} else {
    // Browser summary
    ?>
    <div class="card">
        <h2>📊 Ringkasan</h2>
        <div class="summary">
            <span class="badge badge-ok">✓ Dibuat baru: <?= $stats['created'] ?></span>
            <span class="badge badge-ok">+ Kolom baru: <?= $stats['altered'] ?></span>
            <span class="badge badge-skip">⟳ Dilewati: <?= $stats['skipped'] ?></span>
            <?php if ($stats['errors'] > 0): ?>
            <span class="badge badge-error">✗ Error: <?= $stats['errors'] ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($allOk): ?>
    <div class="card" style="border-color: #2ed573;">
        <h2 style="color: #2ed573;">✅ Migration Berhasil!</h2>
        <p class="log ok">Semua tabel berhasil dibuat/diverifikasi.</p>
    </div>
    <?php else: ?>
    <div class="card" style="border-color: #ff4757;">
        <h2 style="color: #ff4757;">❌ Ada Error!</h2>
        <p class="log error">Cek detail output di atas.</p>
    </div>
    <?php endif; ?>

    <div class="warning-box">
        <strong>⚠️ WAJIB DILAKUKAN SETELAH MIGRATION BERHASIL:</strong>
        Hapus file <code>public/migrate-v2.php</code> dari server!<br>
        File ini tidak boleh dibiarkan accessible di production karena dapat menjadi celah keamanan.
        <pre>rm public/migrate-v2.php
# atau via cPanel File Manager: hapus file ini dari folder public_html/public/</pre>
    </div>
    </body></html>
    <?php
}