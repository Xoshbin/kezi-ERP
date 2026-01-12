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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('deferred_revenue_account_id')->nullable()->after('income_account_id');
            $table->unsignedBigInteger('deferred_expense_account_id')->nullable()->after('expense_account_id');

            $table->foreign('deferred_revenue_account_id')->references('id')->on('accounts')->nullOnDelete();
            $table->foreign('deferred_expense_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['deferred_revenue_account_id']);
            $table->dropForeign(['deferred_expense_account_id']);
            $table->dropColumn(['deferred_revenue_account_id', 'deferred_expense_account_id']);
        });
    }
};
