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
        Schema::table('vendor_bill_lines', function (Blueprint $table) {
            $table->string('shipping_cost_type')->nullable()->after('analytic_account_id');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->string('shipping_cost_type')->nullable()->after('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_bill_lines', function (Blueprint $table) {
            $table->dropColumn('shipping_cost_type');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropColumn('shipping_cost_type');
        });
    }
};
