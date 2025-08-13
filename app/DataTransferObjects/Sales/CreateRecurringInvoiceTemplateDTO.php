<?php

namespace App\DataTransferObjects\Sales;

use App\Enums\RecurringInvoice\RecurringFrequency;
use Carbon\Carbon;

readonly class CreateRecurringInvoiceTemplateDTO
{
    /**
     * @param RecurringInvoiceLineDTO[] $lines
     */
    public function __construct(
        public int $company_id,
        public int $target_company_id,
        public string $name,
        public ?string $description,
        public RecurringFrequency $frequency,
        public Carbon $start_date,
        public ?Carbon $end_date,
        public int $day_of_month,
        public int $month_of_quarter,
        public int $currency_id,
        public int $income_account_id,
        public int $expense_account_id,
        public ?int $tax_id,
        public array $lines,
        public int $created_by_user_id,
        public string $reference_prefix = 'IC-RECURRING',
    ) {}
}
