<?php

namespace Jmeryar\Accounting\Observers;

use Illuminate\Support\Facades\Auth;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryForAssetAcquisitionAction;
use Jmeryar\Accounting\Enums\Assets\AssetStatus;
use Jmeryar\Accounting\Models\Asset;

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
                    app(CreateJournalEntryForAssetAcquisitionAction::class)->execute($asset, $user);
                }
            }
        }
    }

    /**
     * Handle the Asset "deleting" event.
     *
     * Provides a safety net to prevent deletion of assets with financial records,
     * even if the service layer is bypassed.
     */
    public function deleting(Asset $asset): void
    {
        // Safety net: Prevent deletion of non-draft assets
        if ($asset->status !== AssetStatus::Draft) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete a confirmed asset. Only draft assets can be deleted.'
            );
        }

        // Safety net: Prevent deletion of assets with depreciation entries
        if ($asset->depreciationEntries()->exists()) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with depreciation entries. Depreciation history must be preserved.'
            );
        }

        // Safety net: Prevent deletion of assets with journal entries
        if ($asset->journalEntries()->exists()) {
            throw new \Jmeryar\Foundation\Exceptions\DeletionNotAllowedException(
                'Cannot delete an asset with associated journal entries. Financial records must be preserved.'
            );
        }
    }
}
