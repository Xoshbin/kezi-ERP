<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Models\JournalEntry;
use App\Models\User;
use Brick\Money\Money; // Import the Money class
use Brick\Math\RoundingMode; // Import RoundingMode for division
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }

    public function runDepreciation(Asset $asset, User $user): void
    {
        // 1. Calculate the depreciation amount for one period.
        $totalMonths = $asset->useful_life_years * 12;
        if ($totalMonths <= 0) {
            return; // Avoid division by zero
        }
        $depreciableValue = $asset->purchase_value->minus($asset->salvage_value);
        $monthlyAmount = $depreciableValue->dividedBy($totalMonths, RoundingMode::HALF_UP);


        DB::transaction(function () use ($asset, $user, $monthlyAmount) {
            // 2. Create the depreciation entry record.
            // The 'amount' will now be a Money object, and the MoneyCast will handle it.
            $depreciationEntry = $asset->depreciationEntries()->create([
                'depreciation_date' => now(),
                'amount' => $monthlyAmount,
                'status' => 'Posted',
                'currency_id' => $asset->company->currency_id, // MODIFIED: Added the missing currency_id
            ]);

            // 3. Create the journal entry.
            $journalEntry = $this->createJournalEntryForDepreciation($depreciationEntry, $user);
            $depreciationEntry->journal_entry_id = $journalEntry->id;
            $depreciationEntry->save();
        });
    }

    private function createJournalEntryForDepreciation(DepreciationEntry $entry, User $user): JournalEntry
    {
        $asset = $entry->asset; // Get the parent asset
        $company = $asset->company;
        $journalId = $company->default_depreciation_journal_id;
        // MODIFIED: Get currency code for creating zero-value Money objects
        $currencyCode = $company->currency->code;

        if (!$journalId) {
            throw new \RuntimeException('Default depreciation journal is not configured for this company.');
        }

        $lines = [
            // Debit the expense account
            // MODIFIED: Add a zero-value credit to maintain structure
            [
                'account_id' => $asset->depreciation_expense_account_id,
                'debit' => $entry->amount,
                'credit' => Money::of(0, $currencyCode),
            ],
            // Credit the contra-asset account
            // MODIFIED: Add a zero-value debit to maintain structure
            [
                'account_id' => $asset->accumulated_depreciation_account_id,
                'credit' => $entry->amount,
                'debit' => Money::of(0, $currencyCode),
            ],
        ];

        $journalEntryData = [
            'company_id' => $asset->company_id,
            'journal_id' => $journalId,
            'entry_date' => $entry->depreciation_date,
            'reference' => 'DEPR/' . $asset->name . '/' . $entry->depreciation_date->format('Y-m'),
            'description' => 'Depreciation for ' . $asset->name,
            'source_type' => DepreciationEntry::class,
            'source_id' => $entry->id,
            'created_by_user_id' => $user->id,
            'lines' => $lines,
            // MODIFIED: Explicitly set the currency ID for the journal entry
            'currency_id' => $company->currency_id,
        ];

        // MODIFIED: The second parameter 'true' indicates the entry should be posted immediately.
        return $this->journalEntryService->create($journalEntryData, true);
    }
}