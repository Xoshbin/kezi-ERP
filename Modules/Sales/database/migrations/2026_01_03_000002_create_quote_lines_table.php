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
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();

            // Parent quote
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();

            // Product and tax references
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Line item details
            $table->text('description')->comment('Product description or custom description');
            $table->decimal('quantity', 15, 4)->comment('Quantity quoted');
            $table->string('unit')->nullable()->comment('Unit of measure');
            $table->unsignedInteger('line_order')->default(0)->comment('Line ordering within quote');

            // Pricing (in quote currency, stored as minor units)
            $table->bigInteger('unit_price')->comment('Unit price in minor currency units');
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Discount percentage');
            $table->bigInteger('discount_amount')->default(0)->comment('Discount amount in minor currency units');
            $table->bigInteger('subtotal')->comment('Subtotal (quantity * unit_price - discount) in minor currency units');
            $table->bigInteger('tax_amount')->default(0)->comment('Tax amount for this line in minor currency units');
            $table->bigInteger('total')->comment('Total including tax in minor currency units');

            // Company currency amounts (for multi-currency support)
            $table->bigInteger('unit_price_company_currency')->nullable()->comment('Unit price in company currency minor units');
            $table->bigInteger('discount_amount_company_currency')->nullable()->comment('Discount in company currency minor units');
            $table->bigInteger('subtotal_company_currency')->nullable()->comment('Subtotal in company currency minor units');
            $table->bigInteger('tax_amount_company_currency')->nullable()->comment('Tax in company currency minor units');
            $table->bigInteger('total_company_currency')->nullable()->comment('Total in company currency minor units');

            $table->timestamps();

            // Indexes for performance
            $table->index(['quote_id']);
            $table->index(['product_id']);
            $table->index(['quote_id', 'product_id']);
            $table->index(['quote_id', 'line_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_lines');
    }
};
