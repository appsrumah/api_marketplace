<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel product_details: menyimpan data lengkap produk dari
     * GET /product/202309/products/{product_id}
     *
     * Terhubung ke produk_saya via product_id
     */
    public function up(): void
    {
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->comment('FK ke account_shop_tiktok');

            // ─── Identitas Produk ──────────────────────────────────────────
            $table->string('product_id', 50)->comment('TikTok Product ID (sama dengan produk_saya.product_id)');
            $table->string('platform', 30)->default('TIKTOK');
            $table->string('title', 500)->nullable();
            $table->text('description')->nullable()->comment('Deskripsi HTML produk');

            // ─── Status & Audit ────────────────────────────────────────────
            $table->string('product_status', 30)->nullable()
                ->comment('DRAFT, PENDING, FAILED, ACTIVATE, SELLER_DEACTIVATED, PLATFORM_DEACTIVATED, FREEZE, DELETED, SCHEDULED');
            $table->string('listing_quality_tier', 20)->nullable()
                ->comment('POOR, FAIR, GOOD');
            $table->boolean('has_draft')->default(false);
            $table->boolean('is_cod_allowed')->default(false);
            $table->boolean('is_not_for_sale')->default(false);
            $table->boolean('is_pre_owned')->default(false);

            // ─── Kategori & Brand ──────────────────────────────────────────
            $table->json('category_chains')->nullable()->comment('Array kategori hierarki');
            $table->json('brand')->nullable()->comment('Info brand');

            // ─── Gambar & Video ────────────────────────────────────────────
            $table->json('main_images')->nullable()->comment('Array URL gambar utama');
            $table->json('video')->nullable()->comment('Info video produk');

            // ─── Dimensi & Berat ───────────────────────────────────────────
            $table->json('package_dimensions')->nullable()->comment('JSON: length, width, height, unit');
            $table->json('package_weight')->nullable()->comment('JSON: value, unit');

            // ─── SKU / Varian ──────────────────────────────────────────────
            $table->json('skus')->nullable()->comment('Array lengkap SKU dari API: id, seller_sku, price, inventory, dll');

            // ─── Atribut & Sertifikasi ─────────────────────────────────────
            $table->json('product_attributes')->nullable();
            $table->json('certifications')->nullable();
            $table->json('size_chart')->nullable();
            $table->json('product_types')->nullable()->comment('COMBINED_PRODUCT, IN_COMBINED_PRODUCT, etc');

            // ─── Pengiriman ────────────────────────────────────────────────
            $table->string('shipping_insurance_requirement', 30)->nullable()
                ->comment('REQUIRED, OPTIONAL, NOT_SUPPORTED');
            $table->integer('minimum_order_quantity')->nullable();
            $table->json('delivery_options')->nullable();

            // ─── External & Integration ────────────────────────────────────
            $table->string('external_product_id', 100)->nullable()
                ->comment('ID produk di platform external');
            $table->json('integrated_platform_statuses')->nullable()
                ->comment('Status per platform: TIKTOK_SHOP, TOKOPEDIA, dll');
            $table->json('manufacturer_ids')->nullable();

            // ─── Timestamps dari TikTok ────────────────────────────────────
            $table->integer('tiktok_create_time')->nullable()->comment('Unix timestamp');
            $table->integer('tiktok_update_time')->nullable()->comment('Unix timestamp');

            // ─── Raw Data ──────────────────────────────────────────────────
            $table->json('raw_data')->nullable()->comment('JSON asli dari TikTok API');

            $table->timestamps();

            // ─── Foreign Keys ──────────────────────────────────────────────
            $table->foreign('account_id')->references('id')->on('account_shop_tiktok')->cascadeOnDelete();

            // ─── Indexes ───────────────────────────────────────────────────
            $table->unique(['product_id', 'platform'], 'unique_product_platform');
            $table->index('account_id');
            $table->index('product_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
