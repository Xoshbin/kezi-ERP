<?php

namespace Kezi\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class CreateProjectBudgetDTO
{
    /**
     * @param  array<ProjectBudgetLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $project_id,
        public string $name,
        public Carbon $start_date,
        public Carbon $end_date,
        public array $lines,
    ) {}
}
