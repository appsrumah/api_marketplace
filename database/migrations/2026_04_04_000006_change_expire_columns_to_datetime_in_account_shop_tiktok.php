<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // TIMESTAMP hanya support s/d 2038-01-19.
        // TikTok memberi expire tahun 2082–2180, sehingga harus pakai DATETIME (s/d 9999).
        DB::statement("
            ALTER TABLE account_shop_tiktok
            MODIFY access_token_expire_in  DATETIME NULL,
            MODIFY refresh_token_expire_in DATETIME NULL,
            MODIFY token_obtained_at       DATETIME NULL,
            MODIFY last_sync_at            DATETIME NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE account_shop_tiktok
            MODIFY access_token_expire_in  TIMESTAMP NULL,
            MODIFY refresh_token_expire_in TIMESTAMP NULL,
            MODIFY token_obtained_at       TIMESTAMP NULL,
            MODIFY last_sync_at            TIMESTAMP NULL
        ");
    }
};
