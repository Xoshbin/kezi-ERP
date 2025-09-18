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
        // Add performance indexes for stock_moves table
        Schema::table('stock_moves', function (Blueprint $table) {
            // Critical indexes for inventory operations
            $table->index(['from_location_id', 'status'], 'idx_moves_from_location_status');
            $table->index(['to_location_id', 'status'], 'idx_moves_to_location_status');
            $table->index(['move_type', 'status'], 'idx_moves_type_status');

            // Indexes for reporting queries
            $table->index(['company_id', 'move_date'], 'idx_moves_company_date');
            $table->index(['product_id', 'move_date'], 'idx_moves_product_date');
            $table->index(['company_id', 'product_id', 'move_date'], 'idx_moves_company_product_date');
        });

        // Add performance indexes for stock_move_valuations table
        if (Schema::hasTable('stock_move_valuations')) {
            Schema::table('stock_move_valuations', function (Blueprint $table) {
                // Critical indexes for valuation queries
                $table->index(['company_id', 'product_id'], 'idx_valuations_company_product');
                $table->index(['stock_move_id'], 'idx_valuations_move');
                $table->index(['valuation_method'], 'idx_valuations_method');
                $table->index(['move_type'], 'idx_valuations_move_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for stock_moves table
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropIndex('idx_moves_from_location_status');
            $table->dropIndex('idx_moves_to_location_status');
            $table->dropIndex('idx_moves_type_status');
            $table->dropIndex('idx_moves_company_date');
            $table->dropIndex('idx_moves_product_date');
            $table->dropIndex('idx_moves_company_product_date');
        });

        // Drop indexes for stock_move_valuations table
        if (Schema::hasTable('stock_move_valuations')) {
            Schema::table('stock_move_valuations', function (Blueprint $table) {
                $table->dropIndex('idx_valuations_company_product');
                $table->dropIndex('idx_valuations_move');
                $table->dropIndex('idx_valuations_method');
                $table->dropIndex('idx_valuations_move_type');
            });
        }
    }
};
