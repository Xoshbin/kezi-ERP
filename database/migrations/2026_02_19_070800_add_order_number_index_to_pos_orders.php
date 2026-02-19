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
        Schema::table('pos_orders', function (Blueprint $table) {
            // Enforce that order_number is unique within a company.
            // This prevents duplicate order numbers from being created by concurrent syncs.
            $table->unique(['company_id', 'order_number'], 'pos_orders_company_order_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropUnique('pos_orders_company_order_number_unique');
        });
    }
};
