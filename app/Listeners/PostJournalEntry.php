<?php

namespace App\Listeners;

use App\Events\VendorBillConfirmed;
use App\Services\JournalEntryService;
use Illuminate\Contracts\Queue\ShouldQueue;

class PostJournalEntry implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly JournalEntryService $journalEntryService)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(VendorBillConfirmed $event): void
    {
        if ($event->vendorBill->journalEntry) {
            $this->journalEntryService->post($event->vendorBill->journalEntry);
        }
    }
}