<?php

namespace Kezi\Accounting\Listeners\Deferred;

use Illuminate\Contracts\Queue\ShouldQueue;
use Kezi\Accounting\Services\DeferredItemService;
use Kezi\Sales\Events\InvoiceConfirmed;

class CreateDeferredRevenueFromInvoice implements ShouldQueue
{
    public function __construct(
        protected DeferredItemService $deferredItemService
    ) {}

    public function handle(InvoiceConfirmed $event): void
    {
        $invoice = $event->invoice;

        foreach ($invoice->invoiceLines as $line) {
            $this->deferredItemService->createFromInvoiceLine($line);
        }
    }
}
