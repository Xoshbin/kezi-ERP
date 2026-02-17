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
        Schema::table('pos_profiles', function (Blueprint $table) {
            $table->foreignId('stock_location_id')
                ->nullable()
                ->after('is_active')
                ->constrained('stock_locations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_location_id');
        });
    }
};
