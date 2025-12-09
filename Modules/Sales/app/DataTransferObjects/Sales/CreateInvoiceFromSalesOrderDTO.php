<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use Carbon\Carbon;
use Modules\Sales\Models\SalesOrder;

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
