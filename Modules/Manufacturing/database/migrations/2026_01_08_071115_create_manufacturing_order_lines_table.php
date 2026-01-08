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
        Schema::create('manufacturing_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_required', 15, 4);
            $table->decimal('quantity_consumed', 15, 4)->default(0);
            $table->bigInteger('unit_cost')->default(0); // Actual unit cost in minor units
            $table->string('currency_code', 3);
            $table->foreignId('stock_move_id')->nullable()->constrained('stock_moves')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'manufacturing_order_id'], 'mo_lines_company_mo_idx');
            $table->index(['company_id', 'product_id'], 'mo_lines_company_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_order_lines');
    }
};
