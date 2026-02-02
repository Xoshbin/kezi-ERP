<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Kezi\Accounting\Console\Commands\ProcessDepreciations;

Schedule::command(ProcessDepreciations::class)->daily();
Schedule::command(\Kezi\Accounting\Console\Commands\ProcessRecurringTransactionsCommand::class)->daily();
Schedule::job(new \Kezi\Accounting\Jobs\ProcessDeferredEntriesJob)->daily();
