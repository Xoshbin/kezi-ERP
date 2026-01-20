<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('is_active');
            $table->foreignId('parent_product_id')->nullable()->after('is_template')
                ->constrained('products')->nullOnDelete();
            $table->string('variant_sku_suffix')->nullable()->after('parent_product_id');

            $table->index('parent_product_id');
            $table->index('is_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['parent_product_id']);
            $table->dropColumn(['is_template', 'parent_product_id', 'variant_sku_suffix']);
        });
    }
};
