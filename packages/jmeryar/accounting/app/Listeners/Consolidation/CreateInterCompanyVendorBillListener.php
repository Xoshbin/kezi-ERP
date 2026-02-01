<?php

namespace Jmeryar\Accounting\Listeners\Consolidation;

use Jmeryar\Accounting\Services\Consolidation\InterCompanyDocumentService;
use Jmeryar\Sales\Events\InvoiceConfirmed;

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
