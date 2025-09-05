<?php

namespace App\DataTransferObjects\HumanResources;

readonly class CreateLeaveRequestDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public int $leave_type_id,
        public string $request_number,
        public string $start_date,
        public string $end_date,
        public float $days_requested,
        public ?string $reason,
        public ?string $notes,
        public ?int $delegate_employee_id,
        public ?string $delegation_notes,
        /** @var array<int, string>|null */
        public ?array $attachments,
        public int $requested_by_user_id,
    ) {}
}
