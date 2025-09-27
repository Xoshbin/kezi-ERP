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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            
            // PO identification and status
            $table->string('po_number')->nullable()->comment('Auto-generated PO number when confirmed');
            $table->string('status')->default('draft')->comment('draft, confirmed, partially_received, fully_received, cancelled');
            $table->string('reference')->nullable()->comment('External reference or vendor quote number');
            
            // Dates
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Currency and exchange rate
            $table->decimal('exchange_rate_at_creation', 10, 6)->nullable()->comment('Exchange rate when PO was created/confirmed');
            
            // Totals (in PO currency)
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
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['vendor_id', 'status']);
            $table->index(['po_date', 'company_id']);
            $table->unique(['company_id', 'po_number'], 'unique_po_number_per_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
