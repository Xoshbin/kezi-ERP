<?php

namespace Kezi\Accounting\Listeners\Consolidation;

use Kezi\Accounting\Services\Consolidation\InterCompanyDocumentService;
use Kezi\Sales\Events\InvoiceConfirmed;

class CreateInterCompanyVendorBillListener
{
    public function __construct(
        protected InterCompanyDocumentService $service
    ) {}

    public function handle(InvoiceConfirmed $event): void
    {
        $this->service->createReciprocalVendorBill($event->invoice);
    }
}
