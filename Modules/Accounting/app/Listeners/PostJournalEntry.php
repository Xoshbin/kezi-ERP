<?php

namespace Modules\Accounting\Listeners;

use App\Services\JournalEntryService;
use Illuminate\Events\Dispatcher;

class PostJournalEntry
{
    public function __construct(private readonly JournalEntryService $journalEntryService) {}

    public function handleVendorBillConfirmed(\Modules\Purchase\Events\VendorBillConfirmed $event): void
    {
        if ($event->vendorBill->journalEntry) {
            $this->journalEntryService->post($event->vendorBill->journalEntry);
        }
    }

    public function handleInvoiceConfirmed(\Modules\Sales\Events\InvoiceConfirmed $event): void
    {
        if ($event->invoice->journalEntry) {
            $this->journalEntryService->post($event->invoice->journalEntry);
        }
    }

    public function handlePaymentConfirmed(\Modules\Payment\Events\PaymentConfirmed $event): void
    {
        if ($event->payment->journalEntry) {
            $this->journalEntryService->post($event->payment->journalEntry);
        }
    }

    public function handleAdjustmentDocumentPosted(\Modules\Inventory\Events\AdjustmentDocumentPosted $event): void
    {
        if ($event->adjustmentDocument->journalEntry) {
            $this->journalEntryService->post($event->adjustmentDocument->journalEntry);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            \Modules\Purchase\Events\VendorBillConfirmed::class,
            [PostJournalEntry::class, 'handleVendorBillConfirmed']
        );

        $events->listen(
            \Modules\Sales\Events\InvoiceConfirmed::class,
            [PostJournalEntry::class, 'handleInvoiceConfirmed']
        );

        $events->listen(
            \Modules\Payment\Events\PaymentConfirmed::class,
            [PostJournalEntry::class, 'handlePaymentConfirmed']
        );

        $events->listen(
            \Modules\Inventory\Events\AdjustmentDocumentPosted::class,
            [PostJournalEntry::class, 'handleAdjustmentDocumentPosted']
        );
    }
}
