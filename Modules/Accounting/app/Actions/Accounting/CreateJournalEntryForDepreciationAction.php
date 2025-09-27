<?php

namespace Modules\Accounting\Actions\Accounting;

use App\Models\User;
use Brick\Money\Money;
use Modules\Accounting\Models\DepreciationEntry;
use RuntimeException;

class CreateJournalEntryForDepreciationAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction) {}

    public function execute(DepreciationEntry $entry, User $user): JournalEntry
    {
        // 1. Load necessary relationships for context.
        $entry->load('asset.company.currency');
        $asset = $entry->asset;
        $company = $asset->company;
        $journalId = $company->default_depreciation_journal_id;
        $currencyCode = $company->currency->code;

        if (! $journalId) {
            throw new RuntimeException('Default depreciation journal is not configured for this company.');
        }

        // 2. Build the journal entry lines based on depreciation accounting rules.
        $lineDTOs = [
            // Rule: DEBIT the Depreciation Expense account.
            new CreateJournalEntryLineDTO(
                account_id: $asset->depreciation_expense_account_id,
                debit: $entry->amount,
                credit: Money::of(0, $currencyCode),
                description: 'Depreciation Expense for '.$asset->name,
                partner_id: null,
                analytic_account_id: null,
            ),
            // Rule: CREDIT the Accumulated Depreciation contra-asset account.
            new CreateJournalEntryLineDTO(
                account_id: $asset->accumulated_depreciation_account_id,
                debit: Money::of(0, $currencyCode),
                credit: $entry->amount,
                description: 'Accumulated Depreciation for '.$asset->name,
                partner_id: null,
                analytic_account_id: null,
            ),
        ];

        // 3. Prepare the data payload for the action.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $asset->company_id,
            journal_id: $journalId,
            currency_id: $company->currency_id,
            entry_date: $entry->depreciation_date,
            reference: 'DEPR/'.$asset->name.'/'.$entry->depreciation_date->format('Y-m'),
            description: 'Depreciation for '.$asset->name,
            source_type: DepreciationEntry::class,
            source_id: $entry->id,
            created_by_user_id: $user->id,
            is_posted: true,
            lines: $lineDTOs
        );

        // 4. Execute the action and return the result.
        return $this->createJournalEntryAction->execute($journalEntryDTO);
    }
}
