<?php

namespace Jmeryar\Accounting\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Jmeryar\Accounting\Actions\Recurring\ProcessRecurringTransactionAction;
use Jmeryar\Accounting\Enums\Accounting\RecurringStatus;
use Jmeryar\Accounting\Models\RecurringTemplate;

class ProcessRecurringTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring transaction templates.';

    /**
     * Execute the console command.
     */
    public function handle(ProcessRecurringTransactionAction $action)
    {
        $this->info('Starting recurring transactions processing...');

        $templates = RecurringTemplate::where('status', RecurringStatus::Active)
            ->where('next_run_date', '<=', Carbon::now()->startOfDay())
            ->get();

        $count = $templates->count();
        $this->info("Found {$count} templates due for processing.");

        foreach ($templates as $template) {
            try {
                $this->info("Processing template: {$template->name} (ID: {$template->id})");
                $action->execute($template, Carbon::now());
                $this->info("Successfully processed template: {$template->name}");
            } catch (\Exception $e) {
                $this->error("Failed to process template ID {$template->id}: ".$e->getMessage());
                // Continue with next template
            }
        }

        $this->info('Recurring transactions processing completed.');
    }
}
