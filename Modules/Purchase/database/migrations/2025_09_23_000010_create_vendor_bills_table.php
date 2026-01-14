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
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('stock_picking_id')->nullable()->constrained('stock_pickings')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('partners');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('fiscal_position_id')->nullable()->constrained('fiscal_positions')->nullOnDelete();
            $table->string('bill_reference');
            $table->date('bill_date');
            $table->date('accounting_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default(VendorBillStatus::Draft)->index();
            $table->string('three_way_match_status')->nullable()->index();
            $table->string('payment_status')->default('not_paid');
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_tax');
            // Add exchange rate captured at vendor bill creation/posting
            $table->decimal('exchange_rate_at_creation', 20, 10)->nullable();

            // Add company currency amounts (converted amounts)
            $table->unsignedBigInteger('total_amount_company_currency')->nullable();
            $table->unsignedBigInteger('total_tax_company_currency')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->json('reset_to_draft_log')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('incoterm', 3)->nullable()->index(); // FCA, EXW, etc.
            $table->string('incoterm_location')->nullable();
            $table->nullableMorphs('inter_company_source', 'vb_ic_source_idx');
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
