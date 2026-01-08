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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('incoterm')->nullable()->after('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('incoterm')->nullable()->after('status');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('incoterm')->nullable()->after('status');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->string('incoterm')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('incoterm');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('incoterm');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('incoterm');
        });

        Schema::table('vendor_bills', function (Blueprint $table) {
            $table->dropColumn('incoterm');
        });
    }
};
