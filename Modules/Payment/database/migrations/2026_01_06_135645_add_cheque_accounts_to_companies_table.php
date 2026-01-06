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
        Schema::table('companies', function (Blueprint $table) {
            // Asset: Post-Dated Cheques Receivable (from customers)
            $table->foreignId('default_pdc_receivable_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            // Liability: Post-Dated Cheques Payable (to vendors)
            $table->foreignId('default_pdc_payable_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            // Expense: Bank Charges / Bounce Penalties
            $table->foreignId('default_cheque_expense_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['default_pdc_receivable_account_id']);
            $table->dropForeign(['default_pdc_payable_account_id']);
            $table->dropForeign(['default_cheque_expense_account_id']);

            $table->dropColumn([
                'default_pdc_receivable_account_id',
                'default_pdc_payable_account_id',
                'default_cheque_expense_account_id',
            ]);
        });
    }
};
