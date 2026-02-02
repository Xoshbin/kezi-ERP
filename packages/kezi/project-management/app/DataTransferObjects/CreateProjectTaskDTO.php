<?php

namespace Kezi\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;

readonly class CreateProjectTaskDTO
{
    public function __construct(
        public int $company_id,
        public int $project_id,
        public ?int $parent_task_id,
        public string $name,
        public ?string $description,
        public ?int $assigned_to,
        public ?Carbon $start_date,
        public ?Carbon $due_date,
        public ?string $estimated_hours,
        public int $sequence = 0,
    ) {}
}
