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
            $table->bigInteger('discount_amount')->default(0)->after('total_tax');
        });

        Schema::table('pos_order_lines', function (Blueprint $table) {
            $table->bigInteger('discount_amount')->default(0)->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('pos_order_lines', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });
    }
};
