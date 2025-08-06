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
            $table->string('inventory_valuation_method')->default('avco');
            $table->foreignId('default_inventory_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_cogs_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_stock_input_account_id')->nullable()->constrained('accounts');
            $table->foreignId('default_price_difference_account_id')->nullable()->constrained('accounts');
            $table->decimal('average_cost', 15, 4)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['default_inventory_account_id']);
            $table->dropForeign(['default_cogs_account_id']);
            $table->dropForeign(['default_stock_input_account_id']);
            $table->dropForeign(['default_price_difference_account_id']);
            $table->dropColumn([
                'inventory_valuation_method',
                'default_inventory_account_id',
                'default_cogs_account_id',
                'default_stock_input_account_id',
                'default_price_difference_account_id',
                'average_cost',
            ]);
        });
    }
};
