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
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->foreign('default_purchase_journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->foreign('default_bank_journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->foreign('default_sales_journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->foreign('default_depreciation_journal_id')->references('id')->on('journals')->nullOnDelete();
            $table->foreign('default_stock_location_id')->references('id')->on('stock_locations');
            $table->foreign('default_vendor_location_id')->references('id')->on('stock_locations');
        });

        // Add foreign keys for other tables that reference companies
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys from other tables first
        if (Schema::hasTable('accounts')) {
            try {
                Schema::table('accounts', function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                });
            } catch (\Exception) {
                // If the foreign key doesn't exist, continue with the migration rollback
            }
        }

        if (Schema::hasTable('audit_logs')) {
            try {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                });
            } catch (\Exception) {
                // If the foreign key doesn't exist, continue with the migration rollback
            }
        }

        // Drop foreign keys from companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['default_purchase_journal_id']);
            $table->dropForeign(['default_bank_journal_id']);
            $table->dropForeign(['default_sales_journal_id']);
            $table->dropForeign(['default_depreciation_journal_id']);
            $table->dropForeign(['default_stock_location_id']);
            $table->dropForeign(['default_vendor_location_id']);
        });
    }
};
