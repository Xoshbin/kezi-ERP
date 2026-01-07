<?php

namespace Modules\ProjectManagement\DataTransferObjects;

use Illuminate\Support\Carbon;
use Modules\ProjectManagement\Enums\TimesheetStatus;

readonly class CreateTimesheetDTO
{
    /**
     * @param  array<TimesheetLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public Carbon $start_date,
        public Carbon $end_date,
        public TimesheetStatus $status,
        public array $lines,
    ) {}
}
