<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\DepreciationEntry;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function __construct(protected JournalEntryService $journalEntryService)
    {
    }
    
    public function runDepreciation(Asset $asset, User $user): void
    {
        // 1. Calculate the depreciation amount for one period.
        $monthlyAmount = ($asset->purchase_value - $asset->salvage_value) / ($asset->useful_life_years * 12);

        DB::transaction(function () use ($asset, $user, $monthlyAmount) {
            // 2. Create the depreciation entry record.
            $depreciationEntry = $asset->depreciationEntries()->create([
                'depreciation_date' => now(),
                'amount' => $monthlyAmount,
                'status' => 'Posted',
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
        $journalId = config('accounting.defaults.depreciation_journal_id');

        $lines = [
            // Debit the expense account
            [
                'account_id' => $asset->depreciation_expense_account_id,
                'debit' => $entry->amount,
            ],
            // Credit the contra-asset account
            [
                'account_id' => $asset->accumulated_depreciation_account_id,
                'credit' => $entry->amount,
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
        ];

        return $this->journalEntryService->create($journalEntryData);
    }
}
