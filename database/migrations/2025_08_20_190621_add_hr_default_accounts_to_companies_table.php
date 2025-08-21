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
            // HR-related default accounts
            $table->foreignId('default_salary_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_salary_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payroll_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('default_income_tax_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_social_security_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_health_insurance_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_pension_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['default_salary_payable_account_id']);
            $table->dropForeign(['default_salary_expense_account_id']);
            $table->dropForeign(['default_payroll_journal_id']);
            $table->dropForeign(['default_income_tax_payable_account_id']);
            $table->dropForeign(['default_social_security_payable_account_id']);
            $table->dropForeign(['default_health_insurance_payable_account_id']);
            $table->dropForeign(['default_pension_payable_account_id']);

            $table->dropColumn([
                'default_salary_payable_account_id',
                'default_salary_expense_account_id',
                'default_payroll_journal_id',
                'default_income_tax_payable_account_id',
                'default_social_security_payable_account_id',
                'default_health_insurance_payable_account_id',
                'default_pension_payable_account_id',
            ]);
        });
    }
};
