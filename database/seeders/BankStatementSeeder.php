<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BankStatementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $this->command->warn('No company found. Skipping BankStatementSeeder.');
            return;
        }

        $bankAccounts = Account::where('type', 'bank')->where('company_id', $company->id)->get();

        if ($bankAccounts->isEmpty()) {
            $this->command->warn('No bank accounts found. Skipping BankStatementSeeder.');
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $bankAccount = $bankAccounts->random();
            $date = Carbon::now()->subMonths($i)->endOfMonth();

            BankStatement::create([
                'reference' => 'BS-000' . $i,
                'statement_date' => $date,
                'starting_balance' => 1000 * $i,
                'ending_balance' => 1500 * $i, // This will be recalculated by lines later
                'status' => 'posted',
                'journal_id' => $bankAccount->journal_id,
                'company_id' => $company->id,
            ]);
        }
    }
}