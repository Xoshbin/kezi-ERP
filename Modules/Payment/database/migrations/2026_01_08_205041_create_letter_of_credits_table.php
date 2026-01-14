<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Payment\Enums\LetterOfCredit\LCStatus;
use Modules\Payment\Enums\LetterOfCredit\LCType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_of_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('partners')->restrictOnDelete();
            $table->foreignId('issuing_bank_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();

            $table->string('lc_number')->unique();
            $table->string('bank_reference')->nullable();
            $table->string('type')->default(LCType::Import->value)->index();
            $table->string('status')->default(LCStatus::Draft->value)->index();

            // Monetary amounts in minor units
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_company_currency');
            $table->unsignedBigInteger('utilized_amount')->default(0);
            $table->unsignedBigInteger('balance')->default(0);

            // Important dates
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->date('shipment_date')->nullable();

            // Additional details
            $table->string('incoterm')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'status']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_of_credits');
    }
};
