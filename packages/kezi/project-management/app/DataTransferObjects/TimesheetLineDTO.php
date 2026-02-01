<?php

namespace Kezi\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class TimesheetLineDTO
{
    public function __construct(
        public ?int $project_id,
        public ?int $project_task_id,
        public Carbon $date,
        public string $hours,
        public ?string $description,
        public bool $is_billable,
    ) {}
}
