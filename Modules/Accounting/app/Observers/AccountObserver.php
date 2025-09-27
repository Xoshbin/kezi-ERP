<?php

namespace Modules\Accounting\Observers;

class AccountObserver
{
    /**
     * Handle the Account "created" event.
     */
    public function created(\Modules\Accounting\Models\Account $account): void
    {
        //
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(\Modules\Accounting\Models\Account $account): void
    {
        //
    }

    /**
     * Handle the Account "deleting" event.
     */
    public function deleting(\Modules\Accounting\Models\Account $account): bool
    {
        // Use the correct method name from your Account model
        if ($account->journalEntryLines()->exists()) {
            $account->is_deprecated = true;
            $account->save();

            return false; // Cancel the deletion
        }

        return true; // Allow deletion if no transactions exist
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(\Modules\Accounting\Models\Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(\Modules\Accounting\Models\Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(\Modules\Accounting\Models\Account $account): void
    {
        //
    }
}
