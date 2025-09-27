<?php

namespace App\Actions\Assets;

use App\Actions\Accounting\CreateJournalEntryForDepreciationAction;
use App\Enums\Assets\DepreciationEntryStatus;
use App\Models\DepreciationEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostDepreciationEntryAction
{
    public function __construct(protected CreateJournalEntryForDepreciationAction $createJournalEntry) {}

    public function execute(DepreciationEntry $depreciationEntry, User $user): DepreciationEntry
    {
        return DB::transaction(function () use ($depreciationEntry, $user): DepreciationEntry {
            $journalEntry = $this->createJournalEntry->execute($depreciationEntry, $user);

            $depreciationEntry->update([
                'status' => DepreciationEntryStatus::Posted,
                'journal_entry_id' => $journalEntry->id,
            ]);

            $fresh = $depreciationEntry->fresh();
            if (! $fresh) {
                throw new \RuntimeException('Failed to refresh depreciation entry after update');
            }

            return $fresh;
        });
    }
}
