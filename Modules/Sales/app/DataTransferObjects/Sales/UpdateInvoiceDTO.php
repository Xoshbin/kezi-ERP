<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use App\Models\Invoice;

class UpdateInvoiceDTO
{
    /**
     * @param  UpdateInvoiceLineDTO[]  $lines
     */
    public function __construct(
        public readonly \Modules\Sales\Models\Invoice $invoice,
        public readonly int $customer_id,
        public readonly int $currency_id,
        public readonly string $invoice_date,
        public readonly string $due_date,
        public readonly array $lines,
        public readonly ?int $fiscal_position_id,
    ) {}
}
