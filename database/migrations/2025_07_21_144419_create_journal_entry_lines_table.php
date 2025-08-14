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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->foreignId('analytic_account_id')->nullable()->constrained('analytic_accounts');
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->unsignedBigInteger('original_currency_amount')->default(0);
            $table->unsignedBigInteger('exchange_rate_at_transaction')->default(0);
            // Add original currency ID to track the currency of the original transaction
            $table->foreignId('original_currency_id')->nullable()->constrained('currencies')->after('analytic_account_id');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Note: The constraint that either debit or credit must be > 0 (but not both)
        // should be enforced in your application logic (e.g., a FormRequest or Action class).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
