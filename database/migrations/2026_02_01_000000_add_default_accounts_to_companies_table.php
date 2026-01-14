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
            // General Defaults
            $table->foreignId('default_accounts_payable_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_accounts_receivable_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->foreignId('default_tax_receivable_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->foreignId('default_sales_discount_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_gain_loss_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->foreignId('default_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_outstanding_receipts_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            // Journals
            $table->foreignId('default_purchase_journal_id')->nullable(); 
            // We can't easily constrain journals if it's not created yet or logic is complex, 
            // but journals should be created by now (timestamp 2026 is way after 2025). 
            // But let's check if 'journals' table migration exists and is run before this.
            // If this file is 2026, and journals is 2025_07_21_144347, we are safe.
            // Wait, journals table creation might depend on companies too?
            // create_journals_table (2025_07_21_144347)
            // It has foreignId('company_id').
            // So we are safe with a 2026 migration.
            
            $table->foreignId('default_bank_journal_id')->nullable();
            $table->foreignId('default_sales_journal_id')->nullable();
            $table->foreignId('default_depreciation_journal_id')->nullable();
            
            // Inventory
            $table->foreignId('default_stock_location_id')->nullable(); // constrain to stock_locations if available
            $table->foreignId('default_vendor_location_id')->nullable();
            $table->foreignId('default_adjustment_location_id')->nullable();
            
            $table->foreignId('inventory_adjustment_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_stock_input_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_purchase_returns_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // HR-related default accounts
            $table->foreignId('default_salary_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_salary_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_payroll_journal_id')->nullable();
            $table->foreignId('default_income_tax_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_social_security_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_health_insurance_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_pension_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_employee_advance_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();

            // Manufacturing
            $table->foreignId('default_finished_goods_inventory_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_raw_materials_inventory_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_manufacturing_journal_id')->nullable();

            // Cheques / PDC
            $table->foreignId('default_pdc_receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_pdc_payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_cheque_expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
             $table->dropForeign(['default_accounts_payable_id']);
             $table->dropColumn('default_accounts_payable_id');
             $table->dropForeign(['default_accounts_receivable_id']);
             $table->dropColumn('default_accounts_receivable_id');
             
             $table->dropForeign(['default_tax_receivable_id']);
             $table->dropColumn('default_tax_receivable_id');
             $table->dropForeign(['default_tax_account_id']);
             $table->dropColumn('default_tax_account_id');
             
             $table->dropForeign(['default_sales_discount_account_id']);
             $table->dropColumn('default_sales_discount_account_id');
             $table->dropForeign(['default_gain_loss_account_id']);
             $table->dropColumn('default_gain_loss_account_id');
             
             $table->dropForeign(['default_bank_account_id']);
             $table->dropColumn('default_bank_account_id');
             $table->dropForeign(['default_outstanding_receipts_account_id']);
             $table->dropColumn('default_outstanding_receipts_account_id');
             
             $table->dropColumn('default_purchase_journal_id');
             $table->dropColumn('default_bank_journal_id');
             $table->dropColumn('default_sales_journal_id');
             $table->dropColumn('default_depreciation_journal_id');
             
             $table->dropColumn('default_stock_location_id');
             $table->dropColumn('default_vendor_location_id');
             $table->dropColumn('default_adjustment_location_id');
             
             $table->dropForeign(['inventory_adjustment_account_id']);
             $table->dropColumn('inventory_adjustment_account_id');
             $table->dropForeign(['default_stock_input_account_id']);
             $table->dropColumn('default_stock_input_account_id');
             $table->dropForeign(['default_purchase_returns_account_id']);
             $table->dropColumn('default_purchase_returns_account_id');

             $table->dropForeign(['default_salary_payable_account_id']);
             $table->dropColumn('default_salary_payable_account_id');
             $table->dropForeign(['default_salary_expense_account_id']);
             $table->dropColumn('default_salary_expense_account_id');
             $table->dropColumn('default_payroll_journal_id');
             $table->dropForeign(['default_income_tax_payable_account_id']);
             $table->dropColumn('default_income_tax_payable_account_id');
             $table->dropForeign(['default_social_security_payable_account_id']);
             $table->dropColumn('default_social_security_payable_account_id');
             $table->dropForeign(['default_health_insurance_payable_account_id']);
             $table->dropColumn('default_health_insurance_payable_account_id');
             $table->dropForeign(['default_pension_payable_account_id']);
             $table->dropColumn('default_pension_payable_account_id');
             $table->dropForeign(['default_employee_advance_receivable_account_id']);
             $table->dropColumn('default_employee_advance_receivable_account_id');

             $table->dropForeign(['default_finished_goods_inventory_id']);
             $table->dropColumn('default_finished_goods_inventory_id');
             $table->dropForeign(['default_raw_materials_inventory_id']);
             $table->dropColumn('default_raw_materials_inventory_id');
             $table->dropColumn('default_manufacturing_journal_id');

             $table->dropForeign(['default_pdc_receivable_account_id']);
             $table->dropColumn('default_pdc_receivable_account_id');
             $table->dropForeign(['default_pdc_payable_account_id']);
             $table->dropColumn('default_pdc_payable_account_id');
             $table->dropForeign(['default_cheque_expense_account_id']);
             $table->dropColumn('default_cheque_expense_account_id');
        });
    }
};
