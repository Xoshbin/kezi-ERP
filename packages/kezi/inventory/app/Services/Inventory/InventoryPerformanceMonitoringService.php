<?php

namespace Kezi\Inventory\Services\Inventory;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryPerformanceMonitoringService
{
    /**
     * Analyze slow queries in inventory operations
     */
    public function analyzeSlowQueries(): array
    {
        // Enable query logging temporarily
        DB::enableQueryLog();

        $analysis = [
            'slow_queries' => [],
            'recommendations' => [],
            'index_usage' => [],
            'table_sizes' => $this->getTableSizes(),
        ];

        // Check for missing indexes on critical tables
        $analysis['missing_indexes'] = $this->checkMissingIndexes();

        // Analyze query patterns
        $analysis['query_patterns'] = $this->analyzeQueryPatterns();

        return $analysis;
    }

    /**
     * Get table sizes for inventory-related tables
     */
    public function getTableSizes(): array
    {
        $tables = [
            'stock_quants',
            'stock_moves',
            'stock_pickings',
            'stock_reservations',
            'stock_move_lines',
            'lots',
            'inventory_cost_layers',
            'stock_move_valuations',
        ];

        $sizes = [];

        foreach ($tables as $table) {
            try {
                $result = DB::select('
                    SELECT
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                        table_rows
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = ?
                ', [$table]);

                if (! empty($result)) {
                    $sizes[$table] = [
                        'size_mb' => $result[0]->size_mb ?? 0,
                        'rows' => $result[0]->table_rows ?? 0,
                    ];
                }
            } catch (Exception $e) {
                $sizes[$table] = ['size_mb' => 0, 'rows' => 0, 'error' => $e->getMessage()];
            }
        }

        return $sizes;
    }

    /**
     * Check for missing indexes that could improve performance
     */
    public function checkMissingIndexes(): array
    {
        $recommendations = [];

        // Check stock_quants table for common query patterns
        $quantsAnalysis = $this->analyzeTableIndexUsage('stock_quants');
        if ($quantsAnalysis['needs_optimization']) {
            $recommendations[] = [
                'table' => 'stock_quants',
                'issue' => 'High table scan ratio',
                'recommendation' => 'Consider adding composite indexes for frequent WHERE clauses',
                'priority' => 'high',
            ];
        }

        // Check stock_moves table
        $movesAnalysis = $this->analyzeTableIndexUsage('stock_moves');
        if ($movesAnalysis['needs_optimization']) {
            $recommendations[] = [
                'table' => 'stock_moves',
                'issue' => 'Slow date range queries',
                'recommendation' => 'Add index on (company_id, move_date, status)',
                'priority' => 'medium',
            ];
        }

        // Check lots table for FEFO queries
        $lotsAnalysis = $this->analyzeTableIndexUsage('lots');
        if ($lotsAnalysis['needs_optimization']) {
            $recommendations[] = [
                'table' => 'lots',
                'issue' => 'Slow expiration date queries',
                'recommendation' => 'Add index on (company_id, active, expiration_date)',
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Analyze index usage for a specific table
     */
    private function analyzeTableIndexUsage(string $tableName): array
    {
        try {
            // Get index usage statistics
            $indexStats = DB::select('
                SELECT
                    index_name,
                    cardinality,
                    sub_part,
                    packed,
                    nullable,
                    index_type
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = ?
                ORDER BY seq_in_index
            ', [$tableName]);

            // Simple heuristic: if table has very few indexes, it might need optimization
            $indexCount = count(array_unique(array_column($indexStats, 'index_name')));

            return [
                'index_count' => $indexCount,
                'needs_optimization' => $indexCount < 3, // Arbitrary threshold
                'indexes' => $indexStats,
            ];
        } catch (Exception $e) {
            return [
                'index_count' => 0,
                'needs_optimization' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze common query patterns in inventory operations
     */
    public function analyzeQueryPatterns(): array
    {
        $patterns = [
            'fefo_queries' => $this->analyzeFEFOQueries(),
            'reservation_queries' => $this->analyzeReservationQueries(),
            'valuation_queries' => $this->analyzeValuationQueries(),
            'reporting_queries' => $this->analyzeReportingQueries(),
        ];

        return $patterns;
    }

    /**
     * Analyze FEFO (First Expired, First Out) query performance
     */
    private function analyzeFEFOQueries(): array
    {
        $startTime = microtime(true);

        try {
            // Simulate a typical FEFO query
            $result = DB::table('stock_quants as sq')
                ->join('lots as l', 'sq.lot_id', '=', 'l.id')
                ->select('sq.lot_id', 'l.expiration_date', 'sq.quantity', 'sq.reserved_quantity')
                ->where('sq.company_id', 1) // Test with company ID 1
                ->where('sq.product_id', 1) // Test with product ID 1
                ->where('sq.location_id', 1) // Test with location ID 1
                ->whereNotNull('sq.lot_id')
                ->where('l.active', true)
                ->whereRaw('sq.quantity > sq.reserved_quantity')
                ->orderBy('l.expiration_date', 'asc')
                ->limit(10)
                ->get();

            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            return [
                'execution_time_ms' => round($executionTime, 2),
                'result_count' => $result->count(),
                'performance_rating' => $this->rateQueryPerformance($executionTime),
                'recommendations' => $this->getFEFORecommendations($executionTime),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'performance_rating' => 'error',
            ];
        }
    }

    /**
     * Analyze reservation query performance
     */
    private function analyzeReservationQueries(): array
    {
        $startTime = microtime(true);

        try {
            // Simulate a typical reservation lookup query
            $result = DB::table('stock_reservations')
                ->where('company_id', 1)
                ->where('product_id', 1)
                ->where('location_id', 1)
                ->sum('quantity');

            $executionTime = (microtime(true) - $startTime) * 1000;

            return [
                'execution_time_ms' => round($executionTime, 2),
                'performance_rating' => $this->rateQueryPerformance($executionTime),
                'recommendations' => $this->getReservationRecommendations($executionTime),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'performance_rating' => 'error',
            ];
        }
    }

    /**
     * Analyze valuation query performance
     */
    private function analyzeValuationQueries(): array
    {
        $startTime = microtime(true);

        try {
            // Simulate a typical valuation query
            $result = DB::table('stock_move_valuations as smv')
                ->join('stock_moves as sm', 'smv.stock_move_id', '=', 'sm.id')
                ->where('sm.company_id', 1)
                ->where('sm.move_date', '<=', now())
                ->where('sm.status', 'done')
                ->sum('smv.value_amount');

            $executionTime = (microtime(true) - $startTime) * 1000;

            return [
                'execution_time_ms' => round($executionTime, 2),
                'performance_rating' => $this->rateQueryPerformance($executionTime),
                'recommendations' => $this->getValuationRecommendations($executionTime),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'performance_rating' => 'error',
            ];
        }
    }

    /**
     * Analyze reporting query performance
     */
    private function analyzeReportingQueries(): array
    {
        $startTime = microtime(true);

        try {
            // Simulate a typical aging report query
            $result = DB::table('inventory_cost_layers')
                ->select(
                    'product_id',
                    DB::raw('SUM(remaining_quantity) as total_quantity'),
                    DB::raw('SUM(remaining_quantity * cost_per_unit_amount) as total_value'),
                    DB::raw('DATEDIFF(NOW(), purchase_date) as age_days')
                )
                ->where('company_id', 1)
                ->where('remaining_quantity', '>', 0)
                ->groupBy('product_id')
                ->get();

            $executionTime = (microtime(true) - $startTime) * 1000;

            return [
                'execution_time_ms' => round($executionTime, 2),
                'result_count' => $result->count(),
                'performance_rating' => $this->rateQueryPerformance($executionTime),
                'recommendations' => $this->getReportingRecommendations($executionTime),
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'performance_rating' => 'error',
            ];
        }
    }

    /**
     * Rate query performance based on execution time
     */
    private function rateQueryPerformance(float $executionTimeMs): string
    {
        if ($executionTimeMs < 10) {
            return 'excellent';
        } elseif ($executionTimeMs < 50) {
            return 'good';
        } elseif ($executionTimeMs < 200) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get FEFO-specific performance recommendations
     */
    private function getFEFORecommendations(float $executionTimeMs): array
    {
        $recommendations = [];

        if ($executionTimeMs > 100) {
            $recommendations[] = 'Consider adding composite index on (company_id, product_id, location_id, lot_id)';
            $recommendations[] = 'Add index on lots table for (active, expiration_date)';
        }

        if ($executionTimeMs > 200) {
            $recommendations[] = 'Consider implementing result caching for FEFO queries';
            $recommendations[] = 'Evaluate partitioning lots table by expiration date ranges';
        }

        return $recommendations;
    }

    /**
     * Get reservation-specific performance recommendations
     */
    private function getReservationRecommendations(float $executionTimeMs): array
    {
        $recommendations = [];

        if ($executionTimeMs > 50) {
            $recommendations[] = 'Add composite index on (company_id, product_id, location_id)';
        }

        if ($executionTimeMs > 100) {
            $recommendations[] = 'Consider denormalizing reservation totals';
        }

        return $recommendations;
    }

    /**
     * Get valuation-specific performance recommendations
     */
    private function getValuationRecommendations(float $executionTimeMs): array
    {
        $recommendations = [];

        if ($executionTimeMs > 100) {
            $recommendations[] = 'Add composite index on stock_moves (company_id, move_date, status)';
            $recommendations[] = 'Consider materialized views for valuation calculations';
        }

        return $recommendations;
    }

    /**
     * Get reporting-specific performance recommendations
     */
    private function getReportingRecommendations(float $executionTimeMs): array
    {
        $recommendations = [];

        if ($executionTimeMs > 200) {
            $recommendations[] = 'Implement report result caching';
            $recommendations[] = 'Consider pre-calculating aging buckets';
            $recommendations[] = 'Add indexes optimized for reporting queries';
        }

        return $recommendations;
    }

    /**
     * Generate performance optimization report
     */
    public function generateOptimizationReport(): array
    {
        $analysis = $this->analyzeSlowQueries();

        $report = [
            'timestamp' => now()->toISOString(),
            'overall_health' => $this->calculateOverallHealth($analysis),
            'table_analysis' => $analysis['table_sizes'],
            'query_performance' => $analysis['query_patterns'],
            'optimization_recommendations' => $this->prioritizeRecommendations($analysis),
            'next_steps' => $this->getNextSteps($analysis),
        ];

        // Log the report for monitoring
        Log::info('Inventory Performance Report Generated', $report);

        return $report;
    }

    /**
     * Calculate overall system health score
     */
    private function calculateOverallHealth(array $analysis): string
    {
        $scores = [];

        foreach ($analysis['query_patterns'] as $pattern) {
            if (isset($pattern['performance_rating'])) {
                $scores[] = $pattern['performance_rating'];
            }
        }

        $excellentCount = count(array_filter($scores, fn ($s) => $s === 'excellent'));
        $goodCount = count(array_filter($scores, fn ($s) => $s === 'good'));
        $totalCount = count($scores);

        if ($totalCount === 0) {
            return 'unknown';
        }

        $healthyRatio = ($excellentCount + $goodCount) / $totalCount;

        if ($healthyRatio >= 0.8) {
            return 'excellent';
        } elseif ($healthyRatio >= 0.6) {
            return 'good';
        } elseif ($healthyRatio >= 0.4) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Prioritize optimization recommendations
     */
    private function prioritizeRecommendations(array $analysis): array
    {
        $recommendations = [];

        // Collect all recommendations
        foreach ($analysis['query_patterns'] as $patternName => $pattern) {
            if (isset($pattern['recommendations'])) {
                foreach ($pattern['recommendations'] as $rec) {
                    $recommendations[] = [
                        'category' => $patternName,
                        'recommendation' => $rec,
                        'priority' => $pattern['performance_rating'] === 'poor' ? 'high' : 'medium',
                    ];
                }
            }
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];

            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return $recommendations;
    }

    /**
     * Get recommended next steps
     */
    private function getNextSteps(array $analysis): array
    {
        $steps = [];

        $overallHealth = $this->calculateOverallHealth($analysis);

        if ($overallHealth === 'poor') {
            $steps[] = 'Immediate action required: Apply performance indexes migration';
            $steps[] = 'Enable query result caching for inventory operations';
            $steps[] = 'Consider database server optimization';
        } elseif ($overallHealth === 'fair') {
            $steps[] = 'Apply recommended indexes for slow queries';
            $steps[] = 'Implement selective caching for reporting queries';
        } else {
            $steps[] = 'Monitor performance trends';
            $steps[] = 'Consider proactive optimizations for future growth';
        }

        return $steps;
    }
}
