<?php

namespace Jmeryar\Accounting\Database\Seeders;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Jmeryar\Accounting\Models\LockDate;

class LockDateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder depends on the CompanySeeder having been run.
        $company = Company::first();

        if (! $company) {
            $this->command->warn('No companies found, skipping LockDateSeeder.');

            return;
        }

        // Create lock dates for the past 12 months (one per month)
        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::now()->subMonths($i)->endOfMonth();
            LockDate::create([
                'company_id' => $company->id,
                'lock_date' => $date,
                'description' => 'Lock date for '.$date->format('F Y'),
                'status' => 'active',
            ]);
        }

        // Create one additional lock date for the current month (not locked yet)
        $currentMonthDate = Carbon::now()->endOfMonth();
        LockDate::create([
            'company_id' => $company->id,
            'lock_date' => $currentMonthDate,
            'description' => 'Placeholder lock date for '.$currentMonthDate->format('F Y'),
            'status' => 'pending',
        ]);
    }
}
