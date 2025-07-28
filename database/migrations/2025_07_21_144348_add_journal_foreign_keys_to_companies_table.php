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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['default_purchase_journal_id']);
            $table->dropForeign(['default_bank_journal_id']);
            $table->dropForeign(['default_sales_journal_id']);
            $table->dropForeign(['default_depreciation_journal_id']);
        });
    }
};