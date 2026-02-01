<?php

namespace Kezi\Inventory\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Kezi\Inventory\Services\Inventory\ReorderingRuleService;

class RunReorderingSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:run-reordering
                            {--cleanup : Clean up old processed suggestions}
                            {--days-old=30 : Days old for cleanup}';

    /**
     * The console command description.
     */
    protected $description = 'Run the inventory reordering scheduler to generate replenishment suggestions';

    /**
     * Execute the console command.
     */
    public function handle(ReorderingRuleService $reorderingService): int
    {
        $this->info('Starting inventory reordering scheduler...');

        try {
            // Generate new suggestions
            $suggestionsCreated = $reorderingService->generateReplenishmentSuggestions();

            $this->info("Created {$suggestionsCreated} replenishment suggestions.");

            // Show pending suggestions summary
            $pendingSuggestions = $reorderingService->getPendingSuggestionsByPriority();

            $this->table(
                ['Priority', 'Count'],
                [
                    ['Urgent', $pendingSuggestions['urgent']->count()],
                    ['High', $pendingSuggestions['high']->count()],
                    ['Normal', $pendingSuggestions['normal']->count()],
                ]
            );

            // Cleanup old suggestions if requested
            if ($this->option('cleanup')) {
                $daysOld = (int) $this->option('days-old');
                $deletedCount = $reorderingService->cleanupOldSuggestions($daysOld);
                $this->info("Cleaned up {$deletedCount} old suggestions (older than {$daysOld} days).");
            }

            $this->info('Reordering scheduler completed successfully.');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Reordering scheduler failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
