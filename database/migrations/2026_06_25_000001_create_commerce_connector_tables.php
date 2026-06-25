<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commerce_products')) {
            Schema::create('commerce_products', function (Blueprint $table): void {
                $table->id();
                $table->string('source', 40)->default('csv');
                $table->string('external_id', 120)->nullable();
                $table->string('sku', 120)->unique();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('vendor', 160)->nullable();
                $table->string('product_type', 160)->nullable();
                $table->string('material', 255)->nullable();
                $table->string('origin_country', 120)->nullable();
                $table->string('certifications', 500)->nullable();
                $table->text('faq')->nullable();
                $table->text('support_policy')->nullable();
                $table->string('language', 20)->default('en');
                $table->string('status', 40)->default('draft');
                $table->json('raw_payload')->nullable();
                $table->foreignId('knowledge_base_id')->nullable()->constrained('knowledge_bases')->nullOnDelete();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->index(['source', 'external_id']);
                $table->index(['status', 'updated_at']);
            });
        }

        if (! Schema::hasTable('commerce_variants')) {
            Schema::create('commerce_variants', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('commerce_product_id')->constrained('commerce_products')->cascadeOnDelete();
                $table->string('sku', 120);
                $table->string('external_id', 120)->nullable();
                $table->string('option_1', 160)->nullable();
                $table->string('option_2', 160)->nullable();
                $table->string('option_3', 160)->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->string('currency', 10)->default('USD');
                $table->integer('inventory_quantity')->default(0);
                $table->string('inventory_policy', 40)->default('deny');
                $table->decimal('weight', 10, 3)->nullable();
                $table->string('weight_unit', 20)->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['commerce_product_id', 'sku']);
                $table->index(['sku']);
                $table->index(['inventory_quantity']);
            });
        }

        if (! Schema::hasTable('commerce_inventory_snapshots')) {
            Schema::create('commerce_inventory_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('commerce_product_id')->constrained('commerce_products')->cascadeOnDelete();
                $table->foreignId('commerce_variant_id')->nullable()->constrained('commerce_variants')->cascadeOnDelete();
                $table->integer('quantity')->default(0);
                $table->string('location_name', 160)->nullable();
                $table->timestamp('captured_at');
                $table->timestamps();

                $table->index(['commerce_product_id', 'captured_at']);
            });
        }

        if (! Schema::hasTable('commerce_content_drafts')) {
            Schema::create('commerce_content_drafts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('commerce_product_id')->constrained('commerce_products')->cascadeOnDelete();
                $table->string('language', 20)->default('en');
                $table->string('channel', 80)->default('marketplace');
                $table->string('title', 255);
                $table->text('description');
                $table->text('bullets')->nullable();
                $table->text('faq')->nullable();
                $table->text('prompt')->nullable();
                $table->string('generation_mode', 40)->default('template');
                $table->timestamps();

                $table->index(['commerce_product_id', 'language']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_content_drafts');
        Schema::dropIfExists('commerce_inventory_snapshots');
        Schema::dropIfExists('commerce_variants');
        Schema::dropIfExists('commerce_products');
    }
};
