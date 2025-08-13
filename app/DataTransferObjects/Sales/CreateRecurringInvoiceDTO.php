<?php

namespace App\DataTransferObjects\Sales;

use Carbon\Carbon;

readonly class CreateRecurringInvoiceDTO
{
    /**
     * @param RecurringInvoiceLineDTO[] $lines
     */
    public function __construct(
        public int $recurring_template_id,
        public int $company_id,
        public int $target_company_id,
        public int $currency_id,
        public Carbon $invoice_date,
        public Carbon $due_date,
        public array $lines,
        public string $reference,
        public int $income_account_id,
        public int $expense_account_id,
        public ?int $tax_id,
        public int $created_by_user_id,
    ) {}
}
