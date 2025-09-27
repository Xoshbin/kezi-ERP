<?php

namespace Modules\Accounting\Listeners;

use Modules\Payment\Events\PaymentConfirmed;
use Modules\Purchase\Events\VendorBillConfirmed;
use Modules\Sales\Events\InvoiceConfirmed;

class PostJournalEntry
{
    public function __construct(private readonly JournalEntryService $journalEntryService) {}

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

    public function handlePaymentConfirmed(PaymentConfirmed $event): void
    {
        if ($event->payment->journalEntry) {
            $this->journalEntryService->post($event->payment->journalEntry);
        }
    }

    public function handleAdjustmentDocumentPosted(AdjustmentDocumentPosted $event): void
    {
        if ($event->adjustmentDocument->journalEntry) {
            $this->journalEntryService->post($event->adjustmentDocument->journalEntry);
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

        $events->listen(
            PaymentConfirmed::class,
            [PostJournalEntry::class, 'handlePaymentConfirmed']
        );

        $events->listen(
            AdjustmentDocumentPosted::class,
            [PostJournalEntry::class, 'handleAdjustmentDocumentPosted']
        );
    }
}
