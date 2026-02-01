<?php

namespace Jmeryar\Accounting\Listeners\Deferred;

use Illuminate\Contracts\Queue\ShouldQueue;
use Jmeryar\Accounting\Services\DeferredItemService;
use Jmeryar\Sales\Events\InvoiceConfirmed;

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
