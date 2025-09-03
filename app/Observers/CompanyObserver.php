<?php

namespace App\Observers;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Company;

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
    /**
     * Handle the Company "deleting" event.
     * This is a critical guard to prevent orphaning any financial data.
     */
    public function deleting(Company $company): void
    {
        // A comprehensive check for any transactional data.
        if (
            $company->accounts()->exists() ||
            $company->journalEntries()->exists() ||
            $company->invoices()->exists() ||
            $company->vendorBills()->exists() ||
            $company->payments()->exists() ||
            $company->assets()->exists()
        ) {
            throw new DeletionNotAllowedException('Cannot delete a company with associated financial records.');
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
