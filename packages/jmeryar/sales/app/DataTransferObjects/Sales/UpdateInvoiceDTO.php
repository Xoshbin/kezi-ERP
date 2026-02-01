<?php

namespace Jmeryar\Sales\DataTransferObjects\Sales;

use Jmeryar\Foundation\Enums\Incoterm;
use Jmeryar\Sales\Models\Invoice;

class UpdateInvoiceDTO
{
    /**
     * @param  UpdateInvoiceLineDTO[]  $lines
     */
    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $customer_id,
        public readonly int $currency_id,
        public readonly string $invoice_date,
        public readonly string $due_date,
        public readonly array $lines,
        public readonly ?int $fiscal_position_id,
        public readonly ?Incoterm $incoterm = null,
    ) {}
}
