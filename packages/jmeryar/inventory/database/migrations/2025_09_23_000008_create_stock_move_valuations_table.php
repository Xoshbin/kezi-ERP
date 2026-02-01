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
        Schema::create('stock_move_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('stock_move_id')->constrained('stock_moves');
            $table->decimal('quantity', 15, 4);
            $table->bigInteger('cost_impact');

            $table->string('valuation_method');
            $table->string('move_type');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $table->morphs('source');

            // Cost source tracking fields
            $table->string('cost_source')->nullable()->comment('Source of cost determination: vendor_bill, average_cost, cost_layer, unit_price, manual, company_default');
            $table->text('cost_source_reference')->nullable()->comment('Additional context about cost source (e.g., VendorBill:123, CostLayer:456)');
            $table->json('cost_warnings')->nullable()->comment('Warnings generated during cost determination');

            $table->timestamps();

            // Add index for cost source queries
            // Add index for cost source queries
            $table->index(['cost_source', 'company_id']);
            $table->index(['company_id', 'product_id'], 'idx_valuations_company_product');
            $table->index(['stock_move_id'], 'idx_valuations_move');
            $table->index(['valuation_method'], 'idx_valuations_method');
            $table->index(['move_type'], 'idx_valuations_move_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_move_valuations');
    }
};
