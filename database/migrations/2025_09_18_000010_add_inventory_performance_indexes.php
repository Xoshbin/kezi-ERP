<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Helper function to add index only if it doesn't exist
        $addIndexSafely = function ($table, $columns, $indexName) {
            $exists = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            if (empty($exists)) {
                try {
                    \DB::statement("ALTER TABLE {$table} ADD INDEX {$indexName} (" . implode(', ', $columns) . ")");
                } catch (\Exception) {
                    // Ignore errors
                }
            }
        };

        // Add performance indexes for stock_moves table
        $addIndexSafely('stock_moves', ['from_location_id', 'status'], 'idx_moves_from_location_status');
        $addIndexSafely('stock_moves', ['to_location_id', 'status'], 'idx_moves_to_location_status');
        $addIndexSafely('stock_moves', ['move_type', 'status'], 'idx_moves_type_status');
        $addIndexSafely('stock_moves', ['company_id', 'move_date'], 'idx_moves_company_date');
        $addIndexSafely('stock_moves', ['product_id', 'move_date'], 'idx_moves_product_date');
        $addIndexSafely('stock_moves', ['company_id', 'product_id', 'move_date'], 'idx_moves_company_product_date');

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
        // Helper function to check if index exists and is not used by foreign key
        $dropIndexSafely = function ($table, $indexName) {
            $exists = \DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            if (empty($exists)) {
                return; // Index doesn't exist
            }

            // Check if index is used by foreign key constraint
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = ?
                AND CONSTRAINT_NAME != 'PRIMARY'
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND COLUMN_NAME IN (
                    SELECT COLUMN_NAME
                    FROM information_schema.STATISTICS
                    WHERE TABLE_NAME = ? AND INDEX_NAME = ?
                )
            ", [$table, $table, $indexName]);

            if (!empty($foreignKeys)) {
                return; // Index is used by foreign key, skip dropping
            }

            try {
                \DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
            } catch (\Exception) {
                // Ignore errors
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
        $dropIndexSafely('stock_moves', 'idx_moves_from_location_status');
        $dropIndexSafely('stock_moves', 'idx_moves_to_location_status');
        $dropIndexSafely('stock_moves', 'idx_moves_type_status');
        $dropIndexSafely('stock_moves', 'idx_moves_company_date');
        $dropIndexSafely('stock_moves', 'idx_moves_product_date');
        $dropIndexSafely('stock_moves', 'idx_moves_company_product_date');
    }
};
