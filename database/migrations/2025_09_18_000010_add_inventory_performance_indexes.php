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
        // Helper function to add index only if it doesn't exist (database agnostic)
        $addIndexSafely = function ($table, $columns, $indexName) {
            try {
                Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                    $table->index($columns, $indexName);
                });
            } catch (\Exception) {
                // Index might already exist, ignore errors
            }
        };

        // Add performance indexes for stock_moves table
        // Note: Removed indices dependent on columns not present in stock_moves table (product_id, from_location_id, etc.)
        // These columns reside in stock_move_product_lines.
        $addIndexSafely('stock_moves', ['move_type', 'status'], 'idx_moves_type_status');
        $addIndexSafely('stock_moves', ['company_id', 'move_date'], 'idx_moves_company_date');

        // Add performance indexes for stock_move_valuations table
        if (Schema::hasTable('stock_move_valuations')) {
            $addIndexSafely('stock_move_valuations', ['company_id', 'product_id'], 'idx_valuations_company_product');
            $addIndexSafely('stock_move_valuations', ['stock_move_id'], 'idx_valuations_move');
            $addIndexSafely('stock_move_valuations', ['valuation_method'], 'idx_valuations_method');
            $addIndexSafely('stock_move_valuations', ['move_type'], 'idx_valuations_move_type');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to drop index safely (database agnostic)
        $dropIndexSafely = function ($table, $indexName) {
            try {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            } catch (\Exception) {
                // Index might not exist or be used by foreign key, ignore errors
            }
        };

        // Drop indexes for stock_move_valuations table first
        if (Schema::hasTable('stock_move_valuations')) {
            $dropIndexSafely('stock_move_valuations', 'idx_valuations_company_product');
            $dropIndexSafely('stock_move_valuations', 'idx_valuations_move');
            $dropIndexSafely('stock_move_valuations', 'idx_valuations_method');
            $dropIndexSafely('stock_move_valuations', 'idx_valuations_move_type');
        }

        // Drop indexes for stock_moves table
        $dropIndexSafely('stock_moves', 'idx_moves_type_status');
        $dropIndexSafely('stock_moves', 'idx_moves_company_date');
    }
};
