<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nomor telepon (bisa login dengan ini)
            $table->string('phone', 20)->nullable()->unique()->after('email');

            // Peran pengguna: super_admin → admin → operator
            $table->enum('role', ['super_admin', 'admin', 'operator'])
                ->default('operator')
                ->after('password');

            // Status aktif/nonaktif
            $table->boolean('is_active')->default(true)->after('role');

            // Dibuat oleh siapa (super_admin)
            $table->unsignedBigInteger('created_by')->nullable()->after('is_active');

            // Avatar (inisial jika null)
            $table->string('avatar')->nullable()->after('created_by');

            // Waktu login terakhir
            $table->timestamp('last_login_at')->nullable()->after('avatar');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['phone', 'role', 'is_active', 'created_by', 'avatar', 'last_login_at']);
        });
    }
};
