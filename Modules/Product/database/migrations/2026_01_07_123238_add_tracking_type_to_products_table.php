<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tracking_type')->default('none')->after('lot_tracking_enabled');
        });

        // Migrate existing data: lot_tracking_enabled = true → tracking_type = 'lot'
        DB::table('products')
            ->where('lot_tracking_enabled', true)
            ->update(['tracking_type' => 'lot']);

        // Remove old column
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('lot_tracking_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('lot_tracking_enabled')->default(false)->after('average_cost');
        });

        // Reverse migration: tracking_type = 'lot' → lot_tracking_enabled = true
        DB::table('products')
            ->where('tracking_type', 'lot')
            ->update(['lot_tracking_enabled' => true]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tracking_type');
        });
    }
};
