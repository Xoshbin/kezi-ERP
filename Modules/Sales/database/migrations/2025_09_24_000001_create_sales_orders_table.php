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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();

            // Company and user references
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();

            // Sales order identification
            $table->string('so_number')->nullable()->comment('Auto-generated sales order number');
            $table->string('status')->default('draft')->comment('Current status of the sales order');
            $table->string('reference')->nullable()->comment('Customer reference or external order number');

            // Dates
            $table->date('so_date')->comment('Sales order date');
            $table->date('expected_delivery_date')->nullable()->comment('Expected delivery date');
            $table->timestamp('confirmed_at')->nullable()->comment('When the sales order was confirmed');
            $table->timestamp('cancelled_at')->nullable()->comment('When the sales order was cancelled');

            // Currency and exchange rate
            $table->decimal('exchange_rate_at_creation', 12, 6)->nullable()->comment('Exchange rate when SO was created');

            // Totals (in SO currency)
            $table->bigInteger('total_amount')->default(0)->comment('Total amount in minor currency units');
            $table->bigInteger('total_tax')->default(0)->comment('Total tax in minor currency units');

            // Company currency totals (for multi-currency support)
            $table->bigInteger('total_amount_company_currency')->nullable()->comment('Total in company currency minor units');
            $table->bigInteger('total_tax_company_currency')->nullable()->comment('Total tax in company currency minor units');

            // Notes and terms
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();

            // Delivery information
            $table->foreignId('delivery_location_id')->nullable()->constrained('stock_locations')->nullOnDelete();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'so_date']);
            $table->index(['company_id', 'so_number']);
            $table->index('status');
            $table->index('so_date');

            // Unique constraint for SO number within company
            $table->unique(['company_id', 'so_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
