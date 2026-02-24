<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_return_lines', function (Blueprint $table) {
            $table->id();

            // Core relationships
            $table->foreignId('pos_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_order_line_id')
                ->constrained('pos_order_lines')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Quantities and amounts
            $table->decimal('quantity_returned', 16, 4);
            $table->decimal('quantity_available', 16, 4); // From original order line
            $table->bigInteger('unit_price')->default(0); // Original unit price
            $table->bigInteger('refund_amount')->default(0); // Actual refund for this line
            $table->bigInteger('restocking_fee_line')->default(0); // Line-level restocking fee

            // Inventory handling
            $table->boolean('restock')->default(true);
            // Should this item be returned to inventory?
            $table->string('item_condition')->nullable();
            // new, opened, damaged, defective
            $table->text('return_reason_line')->nullable();

            // Product metadata (for reference)
            $table->json('metadata')->nullable();
            // Store variant info, modifiers from original line

            $table->timestamps();

            // Indexes
            $table->index(['pos_return_id']);
            $table->index(['original_order_line_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_return_lines');
    }
};
