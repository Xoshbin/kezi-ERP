<?php

namespace App\Observers;

use App\Models\Company;
use App\Exceptions\DeletionNotAllowedException;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "updated" event.
     */
    public function updated(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "deleting" event.
     * This code runs RIGHT BEFORE a company is deleted.
     */
    public function deleting(Company $company): void
    {
        // Check if the company has any accounts.
        // You should add more checks here for invoices, etc. later.
        if ($company->accounts()->exists()) {
            // If it has accounts, throw our custom exception.
            throw new DeletionNotAllowedException('Cannot delete company with associated financial records.');
        }
    }

    /**
     * Handle the Company "deleted" event.
     */
    public function deleted(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "restored" event.
     */
    public function restored(Company $company): void
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     */
    public function forceDeleted(Company $company): void
    {
        //
    }
}
