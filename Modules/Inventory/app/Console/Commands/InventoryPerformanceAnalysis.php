<?php

namespace Modules\Inventory\Console\Commands;

use App\Services\Inventory\InventoryPerformanceMonitoringService;
use App\Services\Inventory\InventoryQueryOptimizationService;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InventoryPerformanceAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:performance-analysis 
                            {--company= : Specific company ID to analyze}
                            {--warm-cache : Warm up cache after analysis}
                            {--export= : Export results to file}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze inventory system performance and provide optimization recommendations';

    /**
     * Execute the console command.
     */
    public function handle(
        InventoryPerformanceMonitoringService $monitoringService,
        InventoryQueryOptimizationService $optimizationService
    ): int {
        $this->info('🔍 Starting Inventory Performance Analysis...');
        $this->newLine();

        // Get company to analyze
        $company = $this->getCompanyToAnalyze();
        if (!$company) {
            $this->error('No company found to analyze.');
            return Command::FAILURE;
        }

        $this->info("📊 Analyzing performance for company: {$company->name}");
        $this->newLine();

        // Generate performance report
        $this->info('⏱️  Generating performance report...');
        $report = $monitoringService->generateOptimizationReport();

        // Display results
        $this->displayResults($report);

        // Warm up cache if requested
        if ($this->option('warm-cache')) {
            $this->info('🔥 Warming up cache...');
            $optimizationService->warmUpCache($company);
            $this->info('✅ Cache warmed up successfully');
            $this->newLine();
        }

        // Export results if requested
        if ($exportPath = $this->option('export')) {
            $this->exportResults($report, $exportPath);
        }

        $this->displayRecommendations($report);

        return Command::SUCCESS;
    }

    /**
     * Get the company to analyze
     */
    private function getCompanyToAnalyze(): ?Company
    {
        if ($companyId = $this->option('company')) {
            return Company::find($companyId);
        }

        // If no specific company, use the first available company
        return Company::first();
    }

    /**
     * Display performance analysis results
     */
    private function displayResults(array $report): void
    {
        // Overall health
        $healthColor = match($report['overall_health']) {
            'excellent' => 'green',
            'good' => 'cyan',
            'fair' => 'yellow',
            'poor' => 'red',
            default => 'white'
        };

        $this->info("🏥 Overall System Health: <fg={$healthColor}>" . strtoupper($report['overall_health']) . '</fg>');
        $this->newLine();

        // Table sizes
        $this->info('📋 Table Analysis:');
        $tableHeaders = ['Table', 'Size (MB)', 'Rows', 'Status'];
        $tableRows = [];

        foreach ($report['table_analysis'] as $tableName => $data) {
            $status = $this->getTableStatus($data);
            $statusColor = $status === 'OK' ? 'green' : ($status === 'WARNING' ? 'yellow' : 'red');
            
            $tableRows[] = [
                $tableName,
                number_format($data['size_mb'], 2),
                number_format($data['rows']),
                "<fg={$statusColor}>{$status}</fg>"
            ];
        }

        $this->table($tableHeaders, $tableRows);
        $this->newLine();

        // Query performance
        $this->info('⚡ Query Performance Analysis:');
        $queryHeaders = ['Query Type', 'Execution Time (ms)', 'Rating', 'Status'];
        $queryRows = [];

        foreach ($report['query_performance'] as $queryType => $data) {
            if (isset($data['execution_time_ms'])) {
                $rating = $data['performance_rating'];
                $ratingColor = match($rating) {
                    'excellent' => 'green',
                    'good' => 'cyan',
                    'fair' => 'yellow',
                    'poor' => 'red',
                    default => 'white'
                };

                $status = $rating === 'poor' ? '⚠️  NEEDS ATTENTION' : '✅ OK';
                
                $queryRows[] = [
                    ucwords(str_replace('_', ' ', $queryType)),
                    number_format($data['execution_time_ms'], 2),
                    "<fg={$ratingColor}>" . strtoupper($rating) . '</fg>',
                    $status
                ];
            }
        }

        $this->table($queryHeaders, $queryRows);
        $this->newLine();
    }

    /**
     * Get table status based on size and row count
     */
    private function getTableStatus(array $data): string
    {
        if (isset($data['error'])) {
            return 'ERROR';
        }

        $sizeMb = $data['size_mb'];
        $rows = $data['rows'];

        // Simple heuristics for table health
        if ($sizeMb > 1000 || $rows > 1000000) {
            return 'LARGE';
        } elseif ($sizeMb > 100 || $rows > 100000) {
            return 'WARNING';
        } else {
            return 'OK';
        }
    }

    /**
     * Display optimization recommendations
     */
    private function displayRecommendations(array $report): void
    {
        if (empty($report['optimization_recommendations'])) {
            $this->info('🎉 No optimization recommendations - system is performing well!');
            return;
        }

        $this->info('💡 Optimization Recommendations:');
        $this->newLine();

        $highPriorityCount = 0;
        $mediumPriorityCount = 0;

        foreach ($report['optimization_recommendations'] as $index => $rec) {
            $priorityColor = $rec['priority'] === 'high' ? 'red' : 'yellow';
            $priorityIcon = $rec['priority'] === 'high' ? '🔴' : '🟡';
            
            if ($rec['priority'] === 'high') {
                $highPriorityCount++;
            } else {
                $mediumPriorityCount++;
            }

            $this->line(
                "{$priorityIcon} <fg={$priorityColor}>[" . strtoupper($rec['priority']) . "]</fg> " .
                "<fg=cyan>{$rec['category']}</fg>: {$rec['recommendation']}"
            );
        }

        $this->newLine();
        $this->info("📊 Summary: {$highPriorityCount} high priority, {$mediumPriorityCount} medium priority recommendations");
        $this->newLine();

        // Next steps
        if (!empty($report['next_steps'])) {
            $this->info('🚀 Recommended Next Steps:');
            foreach ($report['next_steps'] as $index => $step) {
                $this->line('   ' . ($index + 1) . '. ' . $step);
            }
            $this->newLine();
        }

        // Migration command suggestion
        if ($highPriorityCount > 0) {
            $this->warn('⚠️  High priority issues detected!');
            $this->info('💡 Consider running: php artisan migrate --path=database/migrations/2025_09_18_000010_add_inventory_performance_indexes.php');
            $this->newLine();
        }
    }

    /**
     * Export results to file
     */
    private function exportResults(array $report, string $path): void
    {
        try {
            $exportData = [
                'generated_at' => now()->toISOString(),
                'report' => $report,
                'summary' => [
                    'overall_health' => $report['overall_health'],
                    'total_recommendations' => count($report['optimization_recommendations']),
                    'high_priority_count' => count(array_filter(
                        $report['optimization_recommendations'],
                        fn($r) => $r['priority'] === 'high'
                    )),
                ]
            ];

            $content = json_encode($exportData, JSON_PRETTY_PRINT);
            file_put_contents($path, $content);

            $this->info("📄 Results exported to: {$path}");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("Failed to export results: {$e->getMessage()}");
        }
    }
}
