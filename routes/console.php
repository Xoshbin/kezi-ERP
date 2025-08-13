<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ProcessDepreciations;
use App\Console\Commands\ProcessRecurringInterCompanyCharges;

Schedule::command(ProcessDepreciations::class)->daily();
Schedule::command(ProcessRecurringInterCompanyCharges::class)->daily();
