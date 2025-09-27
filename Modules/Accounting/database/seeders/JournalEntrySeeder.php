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

            // Get the Opening Balance journal
            $openingBalanceJournal = Journal::where('name->en', 'Opening Balance')->where('company_id', $company->id)->firstOrFail();

            // Get the accounts by their codes
            $bankAccountIqd = Account::where('code', '110102')->where('company_id', $company->id)->firstOrFail(); // Bank Account (IQD)
            $cashAccountIqd = Account::where('code', '110202')->where('company_id', $company->id)->firstOrFail(); // Cash (IQD)
            $vehiclesAccount = Account::where('code', '150201')->where('company_id', $company->id)->firstOrFail(); // Vehicles
            $ownersEquityAccount = Account::where('code', '320101')->where('company_id', $company->id)->firstOrFail(); // Owner's Equity

            $lineDTOs = [
                new CreateJournalEntryLineDTO(
                    account_id: $bankAccountIqd->id,
                    debit: Money::of(60000000000, 'IQD'), // 60,000,000.000 IQD (in minor units)
                    credit: Money::of(0, 'IQD'),
                    description: null,
                    partner_id: null,
                    analytic_account_id: null
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $cashAccountIqd->id,
                    debit: Money::of(85000000000, 'IQD'), // 85,000,000.000 IQD (in minor units)
                    credit: Money::of(0, 'IQD'),
                    description: null,
                    partner_id: null,
                    analytic_account_id: null
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $vehiclesAccount->id,
                    debit: Money::of(30000000000, 'IQD'), // 30,000,000.000 IQD (in minor units)
                    credit: Money::of(0, 'IQD'),
                    description: null,
                    partner_id: null,
                    analytic_account_id: null
                ),
                new CreateJournalEntryLineDTO(
                    account_id: $ownersEquityAccount->id,
                    debit: Money::of(0, 'IQD'),
                    credit: Money::of(175000000000, 'IQD'), // 175,000,000.000 IQD (in minor units)
                    description: null,
                    partner_id: null,
                    analytic_account_id: null
                ),
            ];

            $journalEntryDTO = new CreateJournalEntryDTO(
                company_id: $company->id,
                journal_id: $openingBalanceJournal->id,
                currency_id: $company->currency_id,
                entry_date: '2025-09-16', // September 16, 2025 as shown in screenshot
                reference: '1',
                description: 'کردنەوەی سەرمایەی خاوەن', // Kurdish description from screenshot
                created_by_user_id: $user->id,
                is_posted: true,
                lines: $lineDTOs
            );

            app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
        });
    }
}
