<?php

namespace App\Listeners;

use App\Events\InvoiceConfirmed;
use App\Events\VendorBillConfirmed;
use App\Services\JournalEntryService;
use Illuminate\Events\Dispatcher;

class PostJournalEntry
{
    public function __construct(private readonly JournalEntryService $journalEntryService)
    {
    }

    public function handleVendorBillConfirmed(VendorBillConfirmed $event): void
    {
        if ($event->vendorBill->journalEntry) {
            $this->journalEntryService->post($event->vendorBill->journalEntry);
        }
    }

    public function handleInvoiceConfirmed(InvoiceConfirmed $event): void
    {
        if ($event->invoice->journalEntry) {
            $this->journalEntryService->post($event->invoice->journalEntry);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            VendorBillConfirmed::class,
            [PostJournalEntry::class, 'handleVendorBillConfirmed']
        );

        $events->listen(
            InvoiceConfirmed::class,
            [PostJournalEntry::class, 'handleInvoiceConfirmed']
        );
    }
}