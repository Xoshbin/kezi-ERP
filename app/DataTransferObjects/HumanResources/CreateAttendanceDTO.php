<?php

namespace App\DataTransferObjects\HumanResources;

readonly class CreateAttendanceDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public string $attendance_date,
        public ?string $clock_in_time,
        public ?string $clock_out_time,
        public ?string $break_start_time,
        public ?string $break_end_time,
        public string $status, // present, absent, late, half_day, on_leave
        public string $attendance_type, // regular, overtime, holiday, weekend
        public ?string $clock_in_location,
        public ?string $clock_out_location,
        public ?string $clock_in_device,
        public ?string $clock_out_device,
        public ?string $clock_in_ip,
        public ?string $clock_out_ip,
        public ?string $notes,
        public bool $is_manual_entry,
        public ?int $leave_request_id,
        public int $created_by_user_id,
    ) {
    }
}
