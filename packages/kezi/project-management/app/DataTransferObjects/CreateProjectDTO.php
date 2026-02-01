<?php

namespace Kezi\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;
use Kezi\ProjectManagement\Enums\BillingType;

readonly class CreateProjectDTO
{
    public function __construct(
        public int $company_id,
        public string $name,
        public string $code,
        public ?string $description,
        public ?int $manager_id,
        public ?int $customer_id,
        public ?Carbon $start_date,
        public ?Carbon $end_date,
        public ?string $budget_amount,
        public BillingType $billing_type,
        public bool $is_billable,
    ) {}
}
