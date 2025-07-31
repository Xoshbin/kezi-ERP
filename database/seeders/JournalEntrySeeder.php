<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JournalEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::firstOrFail();
            $user = User::firstOrFail();
            $currency = $company->currency;
            $bankJournal = Journal::where('name', 'Bank (IQD)')->where('company_id', $company->id)->firstOrFail();
            $bankAccount = Account::where('code', '110102')->where('company_id', $company->id)->firstOrFail();
            $equityAccount = Account::where('code', '320101')->where('company_id', $company->id)->firstOrFail();

            $journalEntry = JournalEntry::create([
                'company_id' => $company->id,
                'journal_id' => $bankJournal->id,
                'currency_id' => $currency->id,
                'entry_date' => now(),
                'reference' => 'Initial Capital Investment',
                'description' => 'Record the initial capital investment.',
                'total_debit' => Money::of(15000000, 'IQD'),
                'total_credit' => Money::of(15000000, 'IQD'),
                'is_posted' => false,
                'created_by_user_id' => $user->id,
            ]);

            $journalEntry->lines()->createMany([
                [
                    'account_id' => $bankAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => Money::of(15000000, 'IQD'),
                    'credit' => 0,
                    'description' => 'Initial capital deposit',
                ],
                [
                    'account_id' => $equityAccount->id,
                    'currency_id' => $currency->id,
                    'debit' => 0,
                    'credit' => Money::of(15000000, 'IQD'),
                    'description' => 'Owner\'s equity contribution',
                ],
            ]);
        });
    }
}
