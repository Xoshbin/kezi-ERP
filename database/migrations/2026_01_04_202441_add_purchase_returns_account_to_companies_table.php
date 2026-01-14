<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add a default Purchase Returns account column to the companies table.
     * This account is used as the contra-expense account when posting
     * Debit Notes (vendor returns), crediting this account.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('default_purchase_returns_account_id')
                ->nullable()
                ->after('default_sales_discount_account_id')
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
            $table->dropConstrainedForeignId('default_purchase_returns_account_id');
        });
    }
};
