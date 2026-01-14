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
        // Petty Cash Funds
        Schema::create('petty_cash_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('custodian_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();

            $table->unsignedBigInteger('imprest_amount'); // Fixed fund amount (minor units)
            $table->unsignedBigInteger('current_balance'); // Current available balance (minor units)

            $table->string('status')->default('active')->index(); // active, closed

            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        // Petty Cash Vouchers
        Schema::create('petty_cash_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained('petty_cash_funds')->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->foreignId('expense_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();

            $table->unsignedBigInteger('amount'); // Minor units
            $table->date('voucher_date');
            $table->text('description');
            $table->string('receipt_reference')->nullable();

            $table->string('status')->default('draft')->index(); // draft, posted

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
        });

        // Petty Cash Replenishments
        Schema::create('petty_cash_replenishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained('petty_cash_funds')->cascadeOnDelete();
            $table->string('replenishment_number')->unique();

            $table->unsignedBigInteger('amount'); // Minor units
            $table->date('replenishment_date');
            $table->string('payment_method')->default('bank_transfer'); // cash, bank_transfer, cheque
            $table->string('reference')->nullable(); // Bank transfer or cheque reference

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_replenishments');
        Schema::dropIfExists('petty_cash_vouchers');
        Schema::dropIfExists('petty_cash_funds');
    }
};
