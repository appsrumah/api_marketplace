<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel activity_logs: audit trail semua aktivitas penting di sistem.
     * Mencatat siapa, melakukan apa, pada data apa, kapan, dan dari mana.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User yang melakukan aksi; null = sistem/cron');
            $table->string('action', 100)
                ->comment('Aksi yang dilakukan, format: resource.verb — misal: user.login, product.sync, stock.push');
            $table->string('subject_type', 150)->nullable()
                ->comment('Class model yang terdampak, misal: App\Models\User (polymorphic)');
            $table->unsignedBigInteger('subject_id')->nullable()
                ->comment('ID record yang terdampak');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('old_values')->nullable()->comment('Nilai sebelum perubahan');
            $table->json('new_values')->nullable()->comment('Nilai sesudah perubahan');
            $table->text('description')->nullable()->comment('Keterangan bebas');
            $table->enum('level', ['info', 'warning', 'critical'])->default('info');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
