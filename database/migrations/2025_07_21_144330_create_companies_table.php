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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->foreignId('currency_id');
            $table->string('fiscal_country'); // e.g., 'IQ'
            $table->foreignId('parent_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('default_accounts_payable_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_receivable_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_purchase_journal_id')->nullable();
            $table->foreignId('default_bank_journal_id')->nullable();
            $table->foreignId('default_accounts_receivable_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_sales_discount_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_tax_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_sales_journal_id')->nullable();
            $table->foreignId('default_depreciation_journal_id')->nullable();
            $table->foreignId('default_bank_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_outstanding_receipts_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_gain_loss_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('default_stock_location_id')->nullable();
            $table->foreignId('default_vendor_location_id')->nullable();
            $table->foreignId('inventory_adjustment_account_id')->nullable()->constrained('accounts');
            $table->timestamps();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // The name of the foreign key constraint is generated automatically by Laravel
            // following the convention: table_column_foreign
            $table->dropForeign(['company_id']);
        });

        Schema::dropIfExists('companies');
    }
};
