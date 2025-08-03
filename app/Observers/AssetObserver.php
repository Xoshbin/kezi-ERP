<?php

namespace App\Observers;

use App\Actions\Accounting\CreateJournalEntryForAssetAcquisitionAction;
use App\Enums\Assets\AssetStatus;
use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

class AssetObserver
{
    /**
     * Handle the Asset "updated" event.
     */
    public function updated(Asset $asset): void
    {
        // Check if the status was just changed to 'Confirmed'.
        if ($asset->wasChanged('status') && $asset->status === AssetStatus::Confirmed) {
            // Ensure a journal entry doesn't already exist to prevent duplicates.
            if ($asset->journalEntries()->where('source_type', 'asset')->doesntExist()) {
                $user = Auth::user(); // Observers don't have direct access to the acting user, so we use the authenticated user.
                if ($user) {
                    (new CreateJournalEntryForAssetAcquisitionAction())->execute($asset, $user);
                }
            }
        }
    }
}
