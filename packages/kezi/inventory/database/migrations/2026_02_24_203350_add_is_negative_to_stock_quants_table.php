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
        Schema::table('stock_quants', function (Blueprint $table) {
            $table->boolean('is_negative_stock')->default(false)->after('reserved_quantity');
            $table->index('is_negative_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_quants', function (Blueprint $table) {
            $table->dropColumn('is_negative_stock');
        });
    }
};
