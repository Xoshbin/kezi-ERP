<?php

namespace Modules\Accounting\Listeners\Consolidation;

use Modules\Accounting\Services\Consolidation\InterCompanyDocumentService;
use Modules\Sales\Events\InvoiceConfirmed;

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
