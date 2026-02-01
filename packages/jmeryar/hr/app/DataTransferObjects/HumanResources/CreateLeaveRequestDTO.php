<?php

namespace Jmeryar\HR\DataTransferObjects\HumanResources;

readonly class CreateLeaveRequestDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public int $leave_type_id,
        public string $start_date,
        public string $end_date,
        public float $days_requested,
        public int $requested_by_user_id,
        public ?string $request_number = null,
        public ?string $reason = null,
        public ?string $notes = null,
        public ?int $delegate_employee_id = null,
        public ?string $delegation_notes = null,
        /** @var array<int, string>|null */
        public ?array $attachments = null,
    ) {}
}
