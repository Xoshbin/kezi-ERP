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
            } catch (Exception) {
                // Index might already exist, ignore errors
            }
        };

        // Add performance indexes for stock_moves table
        // Add performance indexes for stock_move_product_lines table
        $addIndexSafely('stock_move_product_lines', ['from_location_id', 'to_location_id'], 'idx_product_lines_from_to');
        $addIndexSafely('stock_move_product_lines', ['company_id', 'product_id'], 'idx_product_lines_company_product');
        // Note: status and move_type are on stock_moves, not product lines.
        // We cannot index them here unless we join.
        // The original migration tried to index 'status' on this table, but 'status' is likely only on stock_moves header?
        // Let's check stock_move_product_lines definition again.

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
            } catch (Exception) {
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
        // Drop indexes for stock_move_lines table
        $dropIndexSafely('stock_move_lines', 'idx_lines_from_location_status');
        $dropIndexSafely('stock_move_lines', 'idx_lines_to_location_status');
        $dropIndexSafely('stock_move_lines', 'idx_lines_type_status');
        $dropIndexSafely('stock_move_lines', 'idx_lines_company_date');
        $dropIndexSafely('stock_move_lines', 'idx_lines_product_date');
        $dropIndexSafely('stock_move_lines', 'idx_lines_company_product_date');
    }
};
