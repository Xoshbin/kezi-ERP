<?php

namespace Modules\Accounting\Actions\Assets;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\DepreciationEntry;
use RuntimeException;

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
                throw new RuntimeException('Failed to refresh depreciation entry after update');
            }

            return $fresh;
        });
    }
}
