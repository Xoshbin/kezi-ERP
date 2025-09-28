<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('default_adjustment_location_id')
                ->nullable()
                ->after('default_vendor_location_id')
                ->constrained('stock_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['default_adjustment_location_id']);
            $table->dropColumn('default_adjustment_location_id');
        });
    }
};
