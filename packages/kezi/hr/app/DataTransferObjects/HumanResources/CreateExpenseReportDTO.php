<?php

namespace Kezi\HR\DataTransferObjects\HumanResources;

readonly class CreateExpenseReportDTO
{
    /**
     * @param  list<ExpenseReportLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $cash_advance_id,
        public int $employee_id,
        public string $report_date,
        public array $lines,
        public ?string $notes = null,
    ) {}
}
