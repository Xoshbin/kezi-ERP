<?php

namespace Kezi\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Asset;
use Kezi\Accounting\Models\DepreciationEntry;
use Kezi\Accounting\Models\Journal;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;

class DepreciationEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $depreciableAssets = Asset::where('is_depreciable', true)->get();
            $journal = Journal::where('name->en', 'Fixed Assets Journal')->firstOrFail();
            $depreciationExpenseAccount = Account::where('name->en', 'Depreciation Expense')->firstOrFail();
            $accumulatedDepreciationAccount = Account::where('name->en', 'Accumulated Depreciation')->firstOrFail();
            $company = $depreciableAssets->first()->company;

            $referenceCounter = 1;
            $accumulatedDepreciation = 0;

            foreach ($depreciableAssets as $asset) {
                $monthlyDepreciation = $asset->purchase_cost / $asset->useful_life_months;

                for ($i = 0; $i < 12; $i++) {
                    $postingDate = Carbon::parse($asset->purchase_date)->addMonths($i + 1)->startOfMonth();
                    $accumulatedDepreciation += $monthlyDepreciation;

                    $depreciationEntry = DepreciationEntry::create([
                        'asset_id' => $asset->id,
                        'company_id' => $asset->company_id,
                        'reference' => 'DEP-'.str_pad($referenceCounter++, 4, '0', STR_PAD_LEFT),
                        'depreciation_date' => $postingDate,
                        'amount' => $monthlyDepreciation,
                        'accumulated_depreciation' => $accumulatedDepreciation,
                        'status' => 'posted',
                        'notes' => "Monthly depreciation for {$asset->name} - ".$postingDate->format('F Y'),
                    ]);

                    // Create Journal Entry
                    $journalEntry = JournalEntry::create([
                        'company_id' => $company->id,
                        'journal_id' => $journal->id,
                        'date' => $postingDate,
                        'reference' => $depreciationEntry->reference,
                        'narration' => $depreciationEntry->notes,
                        'total_debit' => $monthlyDepreciation,
                        'total_credit' => $monthlyDepreciation,
                        'status' => 'posted',
                        'posted_at' => now(),
                    ]);

                    // Debit Depreciation Expense
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $depreciationExpenseAccount->id,
                        'partner_id' => null,
                        'debit' => $monthlyDepreciation,
                        'credit' => 0,
                        'narration' => "Depreciation expense for {$asset->name}",
                    ]);

                    // Credit Accumulated Depreciation
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $accumulatedDepreciationAccount->id,
                        'partner_id' => null,
                        'debit' => 0,
                        'credit' => $monthlyDepreciation,
                        'narration' => "Accumulated depreciation for {$asset->name}",
                    ]);
                }
            }
        });
    }
}
