<?php

namespace Jmeryar\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Jmeryar\ProjectManagement\Models\Timesheet;
use Jmeryar\ProjectManagement\Models\TimesheetLine;

class CreateTimesheetAction
{
    public function execute(CreateTimesheetDTO $dto): Timesheet
    {
        return DB::transaction(function () use ($dto) {
            $timesheet = Timesheet::create([
                'company_id' => $dto->company_id,
                'employee_id' => $dto->employee_id,
                'start_date' => $dto->start_date,
                'end_date' => $dto->end_date,
                'status' => $dto->status,
            ]);

            foreach ($dto->lines as $lineDto) {
                TimesheetLine::create([
                    'company_id' => $dto->company_id,
                    'timesheet_id' => $timesheet->id,
                    'project_id' => $lineDto->project_id,
                    'project_task_id' => $lineDto->project_task_id,
                    'date' => $lineDto->date,
                    'hours' => $lineDto->hours,
                    'description' => $lineDto->description,
                    'is_billable' => $lineDto->is_billable,
                ]);
            }

            // Calculate total hours after lines creation (handled by observer usually, but manual recalc ensures consistency)
            $timesheet->recalculateTotalHours();

            return $timesheet;
        });
    }
}
