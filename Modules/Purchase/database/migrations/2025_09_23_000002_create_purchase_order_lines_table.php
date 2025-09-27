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
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            
            // Line details
            $table->string('description');
            $table->decimal('quantity', 15, 4);
            $table->decimal('quantity_received', 15, 4)->default(0)->comment('Quantity already received');
            
            // Pricing (in PO currency)
            $table->bigInteger('unit_price')->comment('Unit price in minor currency units');
            $table->bigInteger('subtotal')->comment('Line subtotal in minor currency units');
            $table->bigInteger('total_line_tax')->default(0)->comment('Line tax in minor currency units');
            $table->bigInteger('total')->comment('Line total including tax in minor currency units');
            
            // Company currency amounts (for multi-currency support)
            $table->bigInteger('unit_price_company_currency')->nullable()->comment('Unit price in company currency minor units');
            $table->bigInteger('subtotal_company_currency')->nullable()->comment('Subtotal in company currency minor units');
            $table->bigInteger('total_line_tax_company_currency')->nullable()->comment('Tax in company currency minor units');
            $table->bigInteger('total_company_currency')->nullable()->comment('Total in company currency minor units');
            
            // Expected delivery
            $table->date('expected_delivery_date')->nullable();
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['purchase_order_id', 'product_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
