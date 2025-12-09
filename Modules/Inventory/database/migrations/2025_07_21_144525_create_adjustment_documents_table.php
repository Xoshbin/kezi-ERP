<?php


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentStatus;

return new class() extends Migration {
    /**
     * In an accounting system, direct credit_notes tables are typically consolidated into a more general adjustment_documents table
     * to handle various types of financial adjustments,
     * including credit notes, debit notes, and miscellaneous adjustments.
     * This approach ensures a unified and auditable trail for all corrections to posted financial records
     */
    public function up(): void
    {
        Schema::create('adjustment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('original_vendor_bill_id')->nullable()->constrained('vendor_bills');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('currency_id')->constrained('currencies');
            // Add subtotal field (missing from original schema but present in VendorBill)
            $table->unsignedBigInteger('subtotal');

            // Add exchange rate captured at adjustment document creation/posting
            $table->decimal('exchange_rate_at_creation', 20, 10)->nullable();

            // Add company currency amounts (converted amounts)
            $table->unsignedBigInteger('subtotal_company_currency')->nullable();
            $table->unsignedBigInteger('total_amount_company_currency')->nullable();
            $table->unsignedBigInteger('total_tax_company_currency')->nullable();
            $table->string('type'); // 'credit note', 'debit note', 'miscellaneous adjustment'
            $table->date('date');
            $table->string('reference_number');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_tax');
            $table->text('reason');
            $table->string('status')->default(AdjustmentDocumentStatus::Draft->value); // 'draft', 'posted'
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // Ensure uniqueness for reference_number combined with type and company_id,
            // as reference numbers might be unique per type/company, not globally.
            // This composite unique constraint complements the single unique constraint
            // on 'reference_number' if it's meant to be globally unique.
            // For a system where reference_number is unique *per type and company*,
            // a unique index would be: ['company_id', 'type', 'reference_number']
            // If reference_number is guaranteed to be globally unique across all types/companies
            // once posted, the initial $table->string('reference_number')->unique(); is sufficient.
            // is usually unique within a journal or company for a specific document type.
            // To align with this, consider:
            // $table->unique(['company_id', 'type', 'reference_number']);
            // However, if the single 'reference_number' is enforced as globally unique (e.g., system-wide unique ID),
            // then the basic unique constraint is sufficient. The sources suggest "unique for the type/company" [1],
            // implying uniqueness is scoped. Thus, a composite unique index is more appropriate:

            $table->unique(['company_id', 'type', 'reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_documents');
    }
};
