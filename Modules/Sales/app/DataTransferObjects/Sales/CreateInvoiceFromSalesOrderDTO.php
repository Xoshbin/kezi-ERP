<?php

namespace App\DataTransferObjects\Sales;

use App\Models\SalesOrder;
use Carbon\Carbon;

/**
 * Data Transfer Object for creating an invoice from a sales order
 */
readonly class CreateInvoiceFromSalesOrderDTO
{
    public function __construct(
        public SalesOrder $salesOrder,
        public Carbon $invoice_date,
        public Carbon $due_date,
        public int $default_income_account_id,
        public ?int $fiscal_position_id = null,
        public ?int $payment_term_id = null,
    ) {}
}
