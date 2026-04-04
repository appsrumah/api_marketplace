<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing bigInteger second-values to proper timestamps
        // before changing the column type
        DB::statement("
            ALTER TABLE account_shop_tiktok
            MODIFY access_token_expire_in TIMESTAMP NULL,
            MODIFY refresh_token_expire_in TIMESTAMP NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE account_shop_tiktok
            MODIFY access_token_expire_in BIGINT NULL,
            MODIFY refresh_token_expire_in BIGINT NULL
        ");
    }
};
