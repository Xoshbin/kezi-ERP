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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            // Company and customer references
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Quote identification
            $table->string('quote_number')->nullable()->comment('Auto-generated quote number');
            $table->date('quote_date')->comment('Quote date');
            $table->date('valid_until')->comment('Quote expiration date');

            // State management
            $table->string('status')->default('draft')->comment('Quote status: draft, sent, accepted, rejected, expired, converted, cancelled');

            // Versioning
            $table->unsignedInteger('version')->default(1)->comment('Quote version number');
            $table->foreignId('previous_version_id')->nullable()->constrained('quotes')->nullOnDelete();

            // Conversion tracking
            $table->foreignId('converted_to_sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('converted_to_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->comment('When the quote was converted');

            // Currency and exchange rate
            $table->decimal('exchange_rate', 18, 8)->default(1.0)->comment('Exchange rate when quote was created');

            // Totals (in quote currency, stored as minor units)
            $table->bigInteger('subtotal')->default(0)->comment('Subtotal in minor currency units');
            $table->bigInteger('tax_total')->default(0)->comment('Total tax in minor currency units');
            $table->bigInteger('discount_total')->default(0)->comment('Total discount in minor currency units');
            $table->bigInteger('total')->default(0)->comment('Grand total in minor currency units');

            // Company currency totals (for multi-currency support)
            $table->bigInteger('subtotal_company_currency')->nullable()->comment('Subtotal in company currency minor units');
            $table->bigInteger('tax_total_company_currency')->nullable()->comment('Tax in company currency minor units');
            $table->bigInteger('discount_total_company_currency')->nullable()->comment('Discount in company currency minor units');
            $table->bigInteger('total_company_currency')->nullable()->comment('Total in company currency minor units');

            // Notes and terms
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('rejection_reason')->nullable()->comment('Reason for rejection if status is rejected');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'partner_id']);
            $table->index(['company_id', 'quote_date']);
            $table->index(['company_id', 'quote_number']);
            $table->index(['company_id', 'valid_until']);
            $table->index('status');
            $table->index('quote_date');
            $table->index('valid_until');

            // Unique constraint for quote number within company
            $table->unique(['company_id', 'quote_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
