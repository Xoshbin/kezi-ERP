<?php

namespace Modules\ProjectManagement\DataTransferObjects;

readonly class ProjectBudgetLineDTO
{
    public function __construct(
        public int $account_id,
        public string $budgeted_amount,
        public ?string $description = null,
    ) {}
}
