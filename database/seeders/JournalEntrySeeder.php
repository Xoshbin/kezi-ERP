<?php

namespace Database\Seeders;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
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
            $bankJournal = Journal::where('name->en', 'Bank (IQD)')->where('company_id', $company->id)->firstOrFail();
            $bankAccount = Account::where('code', '110102')->where('company_id', $company->id)->firstOrFail();
            $equityAccount = Account::where('code', '320101')->where('company_id', $company->id)->firstOrFail();

            $lineDTOs = [
                new CreateJournalEntryLineDTO(
                    account_id: $bankAccount->id,
                    debit: Money::of(15000000, 'IQD'),
                    credit: Money::of(0, 'IQD'),
                    description: 'Initial capital deposit',
                    partner_id: null,
                    analytic_account_id: null
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $equityAccount->id,
                    debit: Money::of(0, 'IQD'),
                    credit: Money::of(15000000, 'IQD'),
                    description: 'Owner\'s equity contribution',
                    partner_id: null,
                    analytic_account_id: null
                ),
            ];

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $bankJournal->id,
                currency_id: $company->currency_id,
                entry_date: now()->format('Y-m-d'),
                reference: 'Initial Capital Investment',
                description: 'Record the initial capital investment.',
                created_by_user_id: $user->id,
                is_posted: false,
                lines: $lineDTOs
            );

            app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
        });
    }
}
