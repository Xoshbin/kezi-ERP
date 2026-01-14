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
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('default_finished_goods_inventory_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->foreignId('default_raw_materials_inventory_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->foreignId('default_manufacturing_journal_id')
                ->nullable()
                ->constrained('journals')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['default_finished_goods_inventory_id']);
            $table->dropForeign(['default_raw_materials_inventory_id']);
            $table->dropForeign(['default_manufacturing_journal_id']);

            $table->dropColumn([
                'default_finished_goods_inventory_id',
                'default_raw_materials_inventory_id',
                'default_manufacturing_journal_id',
            ]);
        });
    }
};
