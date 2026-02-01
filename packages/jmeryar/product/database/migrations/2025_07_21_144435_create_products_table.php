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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('income_account_id')->nullable()->constrained('accounts');
            $table->foreignId('deferred_revenue_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('expense_account_id')->nullable()->constrained('accounts');
            $table->foreignId('deferred_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->json('name');
            $table->string('sku');
            $table->json('description')->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();
            $table->boolean('has_price_override')->default(false);
            $table->string('type'); // 'service', 'storable product'
            $table->boolean('is_active')->default(true);

            // Variants and Attributes
            $table->boolean('is_template')->default(false);
            $table->foreignId('parent_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('variant_sku_suffix')->nullable();
            $table->json('product_attributes')->nullable();

            $table->timestamps();

            $table->string('inventory_valuation_method')->default('avco');
            $table->foreignId('default_inventory_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_cogs_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_stock_input_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_price_difference_account_id')->nullable()->constrained('accounts');
            $table->unsignedBigInteger('average_cost')->default(0);
            $table->integer('quantity_on_hand')->default(0);
            $table->decimal('weight', 15, 4)->default(0);
            $table->decimal('volume', 15, 4)->default(0);
            $table->string('tracking_type')->default('none');

            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->index('parent_product_id');
            $table->index('is_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
