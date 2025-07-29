<?php

namespace App\Services;

use App\Actions\Accounting\CreateJournalEntryForDepreciationAction;
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

            // 3. Create and execute the new, dedicated action.
            $journalEntry = (new CreateJournalEntryForDepreciationAction())->execute($depreciationEntry, $user);

            $depreciationEntry->journal_entry_id = $journalEntry->id;
            $depreciationEntry->save();
        });
    }
}
