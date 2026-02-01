<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('customer_id')->constrained('partners');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->foreignId('source_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('fiscal_position_id')->nullable()->constrained('fiscal_positions')->nullOnDelete();
            $table->foreignId('dunning_level_id')->nullable()->constrained('dunning_levels')->nullOnDelete();
            $table->timestamp('last_dunning_date')->nullable();
            $table->date('next_dunning_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status')->default(InvoiceStatus::Draft->value)->index(); // 'draft', 'posted', 'paid', 'cancelled'
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_tax');
            // Add exchange rate captured at invoice creation/posting
            $table->decimal('exchange_rate_at_creation', 20, 10)->nullable();

            // Add company currency amounts (converted amounts)
            $table->unsignedBigInteger('total_amount_company_currency')->nullable();
            $table->unsignedBigInteger('total_tax_company_currency')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('reset_to_draft_log')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('incoterm', 3)->nullable()->index(); // FCA, EXW, etc.
            $table->string('incoterm_location')->nullable();
            $table->nullableMorphs('inter_company_source', 'inv_ic_source_idx');
            $table->timestamps();

            // A truly conditional unique index requires raw SQL.
            // For simplicity, a standard unique index is defined here.
            // Your application must ensure 'invoice_number' is only set on 'Posted' status.
            $table->unique(['company_id', 'invoice_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
