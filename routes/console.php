<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Jmeryar\Accounting\Console\Commands\ProcessDepreciations;

Schedule::command(ProcessDepreciations::class)->daily();
Schedule::command(\Jmeryar\Accounting\Console\Commands\ProcessRecurringTransactionsCommand::class)->daily();
Schedule::job(new \Jmeryar\Accounting\Jobs\ProcessDeferredEntriesJob)->daily();
