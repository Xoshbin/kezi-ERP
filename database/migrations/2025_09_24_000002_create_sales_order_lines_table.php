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
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            
            // Parent sales order
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            
            // Product and tax references
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            
            // Line item details
            $table->text('description')->comment('Product description or custom description');
            $table->decimal('quantity', 15, 2)->comment('Quantity ordered');
            $table->decimal('quantity_delivered', 15, 2)->default(0)->comment('Quantity already delivered');
            $table->decimal('quantity_invoiced', 15, 2)->default(0)->comment('Quantity already invoiced');
            
            // Pricing (in sales order currency)
            $table->bigInteger('unit_price')->comment('Unit price in minor currency units');
            $table->bigInteger('subtotal')->comment('Subtotal (quantity * unit_price) in minor currency units');
            $table->bigInteger('total_line_tax')->comment('Total tax for this line in minor currency units');
            $table->bigInteger('total')->comment('Total including tax in minor currency units');
            
            // Company currency amounts (for multi-currency support)
            $table->bigInteger('unit_price_company_currency')->nullable()->comment('Unit price in company currency minor units');
            $table->bigInteger('subtotal_company_currency')->nullable()->comment('Subtotal in company currency minor units');
            $table->bigInteger('total_line_tax_company_currency')->nullable()->comment('Tax in company currency minor units');
            $table->bigInteger('total_company_currency')->nullable()->comment('Total in company currency minor units');
            
            // Additional details
            $table->date('expected_delivery_date')->nullable()->comment('Expected delivery date for this line');
            $table->text('notes')->nullable()->comment('Line-specific notes');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['sales_order_id']);
            $table->index(['product_id']);
            $table->index(['sales_order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
