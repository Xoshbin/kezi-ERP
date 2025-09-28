<?php

namespace Modules\Sales\DataTransferObjects\Sales;

class CreateInvoiceDTO
{
    /**
     * @param  CreateInvoiceLineDTO[]  $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $customer_id,
        public readonly int $currency_id,
        public readonly string $invoice_date,
        public readonly string $due_date,
        public readonly array $lines,
        public readonly ?int $fiscal_position_id,
        public readonly ?int $payment_term_id = null,
    ) {
    }
}
