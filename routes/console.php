<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use Modules\Accounting\Console\Commands\ProcessDepreciations;

Schedule::command(ProcessDepreciations::class)->daily();
Schedule::job(new \Modules\Accounting\Jobs\ProcessDeferredEntriesJob)->daily();
