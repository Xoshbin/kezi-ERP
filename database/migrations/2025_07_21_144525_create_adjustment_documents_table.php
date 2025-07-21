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
        Schema::create('adjustment_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('original_invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('original_vendor_bill_id')->nullable()->constrained('vendor_bills');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->string('type'); // 'Credit Note', 'Debit Note', 'Miscellaneous Adjustment'
            $table->date('date');
            $table->string('reference_number');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('total_tax', 15, 2);
            $table->text('reason');
            $table->string('status')->default('Draft'); // 'Draft', 'Posted'
            $table->timestamps();

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
