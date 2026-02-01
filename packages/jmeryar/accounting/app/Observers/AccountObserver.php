<?php

namespace Jmeryar\Accounting\Observers;

use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Services\AccountGroupService;

class AccountObserver
{
    public function __construct(
        private readonly AccountGroupService $accountGroupService
    ) {}

    /**
     * Handle the Account "saving" event.
     * Auto-assign account to the most specific matching group based on code.
     */
    public function saving(Account $account): void
    {
        // Only assign to group if the account has a code
        if (! empty($account->code)) {
            $this->accountGroupService->assignAccountToGroup($account);
        }
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "deleting" event.
     */
    public function deleting(Account $account): bool
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
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}
