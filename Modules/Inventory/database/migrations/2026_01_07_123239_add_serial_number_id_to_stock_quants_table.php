<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_quants', function (Blueprint $table) {
            $table->foreignId('serial_number_id')
                ->nullable()
                ->after('lot_id')
                ->constrained('serial_numbers')
                ->cascadeOnDelete();

            $table->index(['serial_number_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_quants', function (Blueprint $table) {
            $table->dropForeign(['serial_number_id']);
            $table->dropColumn('serial_number_id');
        });
    }
};
