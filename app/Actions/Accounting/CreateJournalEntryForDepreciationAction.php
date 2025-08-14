<?php

namespace App\Actions\Accounting;

use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Models\DepreciationEntry;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money;

class CreateJournalEntryForDepreciationAction
{
    public function __construct(private readonly CreateJournalEntryAction $createJournalEntryAction)
    {
    }

    public function execute(DepreciationEntry $entry, User $user): JournalEntry
    {
        // 1. Load necessary relationships for multi-currency handling.
        $entry->load('asset.company.currency', 'asset.currency');
        $asset = $entry->asset;
        $company = $asset->company;
        $baseCurrency = $company->currency;
        $assetCurrency = $asset->currency;
        $journalId = $company->default_depreciation_journal_id;

        if (!$journalId) {
            throw new \RuntimeException('Default depreciation journal is not configured for this company.');
        }

        // Determine the exchange rate. If it's the same currency, the rate is 1.
        $exchangeRate = ($baseCurrency->id === $assetCurrency->id) ? 1.0 : $assetCurrency->exchange_rate;

        // Convert depreciation amount to company base currency
        $depreciationAmountInBase = Money::of(
            $entry->amount->getAmount()->multipliedBy($exchangeRate),
            $baseCurrency->code,
            null,
            \Brick\Math\RoundingMode::HALF_UP
        );

        $zeroAmountInBase = Money::zero($baseCurrency->code);

        // 2. Build the journal entry lines based on depreciation accounting rules.
        $lineDTOs = [
            // Rule: DEBIT the Depreciation Expense account.
            new CreateJournalEntryLineDTO(
                account_id: $asset->depreciation_expense_account_id,
                debit: $depreciationAmountInBase,
                credit: $zeroAmountInBase,
                description: 'Depreciation Expense for ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $entry->amount, // Original Money object
                original_currency_id: $asset->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
            // Rule: CREDIT the Accumulated Depreciation contra-asset account.
            new CreateJournalEntryLineDTO(
                account_id: $asset->accumulated_depreciation_account_id,
                debit: $zeroAmountInBase,
                credit: $depreciationAmountInBase,
                description: 'Accumulated Depreciation for ' . $asset->name,
                partner_id: null,
                analytic_account_id: null,
                original_currency_amount: $entry->amount, // Original Money object
                original_currency_id: $asset->currency_id, // Original currency ID
                exchange_rate_at_transaction: $exchangeRate,
            ),
        ];

        // 3. Prepare the data payload for the action.
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $asset->company_id,
            journal_id: $journalId,
            currency_id: $baseCurrency->id, // Journal entry is always in company base currency
            entry_date: $entry->depreciation_date,
            reference: 'DEPR/' . $asset->name . '/' . $entry->depreciation_date->format('Y-m'),
            description: 'Depreciation for ' . $asset->name,
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
