<?php

use App\Models\VendorBill;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->string('status')->default(VendorBill::STATUS_DRAFT)->index(); // 'draft', 'posted', 'paid', 'canceled
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('total_tax');
            $table->timestamp('posted_at')->nullable();
            $table->json('reset_to_draft_log')->nullable();
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
