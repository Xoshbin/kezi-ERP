<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('customer_id')->constrained('partners');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('fiscal_position_id')->nullable()->constrained('fiscal_positions')->onDelete('set null');
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
