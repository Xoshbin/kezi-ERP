<?php

namespace Jmeryar\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class CreateProjectInvoiceDTO
{
    public function __construct(
        public int $company_id,
        public int $project_id,
        public Carbon $period_start,
        public Carbon $period_end,
        public bool $include_labor = true,
        public bool $include_expenses = true,
    ) {}
}
