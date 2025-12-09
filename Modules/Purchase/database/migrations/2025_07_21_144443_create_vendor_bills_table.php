<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('vendor_id')->constrained('partners');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->string('bill_reference');
            $table->date('bill_date');
            $table->date('accounting_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default(VendorBillStatus::Draft)->index(); // 'draft', 'posted', 'paid', 'canceled
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_tax');
            // Add exchange rate captured at vendor bill creation/posting
            $table->decimal('exchange_rate_at_creation', 20, 10)->nullable();

            // Add company currency amounts (converted amounts)
            $table->unsignedBigInteger('total_amount_company_currency')->nullable();
            $table->unsignedBigInteger('total_tax_company_currency')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('reset_to_draft_log')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
