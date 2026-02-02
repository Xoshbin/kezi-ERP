<?php

namespace Kezi\Sales\Console\Commands;

use Illuminate\Console\Command;
use Kezi\Sales\Services\QuoteService;

/**
 * Artisan command to mark expired quotes.
 *
 * This command should be scheduled to run daily to automatically
 * update the status of quotes that have passed their validity date.
 */
class ExpireQuotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark quotes that have passed their validity date as expired';

    /**
     * Execute the console command.
     */
    public function handle(QuoteService $quoteService): int
    {
        $this->info('Checking for expired quotes...');

        $count = $quoteService->checkExpiredQuotes();

        if ($count > 0) {
            $this->info("Marked {$count} quote(s) as expired.");
        } else {
            $this->info('No expired quotes found.');
        }

        return Command::SUCCESS;
    }
}
