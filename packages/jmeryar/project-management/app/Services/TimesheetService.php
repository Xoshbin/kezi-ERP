<?php

namespace Jmeryar\ProjectManagement\Services;

use App\Models\User;
use Jmeryar\ProjectManagement\Actions\ApproveTimesheetAction;
use Jmeryar\ProjectManagement\Actions\CreateTimesheetAction;
use Jmeryar\ProjectManagement\Actions\RejectTimesheetAction;
use Jmeryar\ProjectManagement\Actions\SubmitTimesheetAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateTimesheetDTO;
use Jmeryar\ProjectManagement\DataTransferObjects\TimesheetLineDTO;
use Jmeryar\ProjectManagement\Models\Timesheet;

class TimesheetService
{
    public function __construct(
        protected CreateTimesheetAction $createTimesheetAction,
        protected SubmitTimesheetAction $submitTimesheetAction,
        protected ApproveTimesheetAction $approveTimesheetAction,
        protected RejectTimesheetAction $rejectTimesheetAction,
    ) {}

    public function createTimesheet(CreateTimesheetDTO $dto): Timesheet
    {
        // Add business validation here if needed (e.g., overlapping dates)
        return $this->createTimesheetAction->execute($dto);
    }

    public function submitTimesheet(Timesheet $timesheet): void
    {
        $this->submitTimesheetAction->execute($timesheet);
    }

    public function approveTimesheet(Timesheet $timesheet, User $approver): void
    {
        $this->approveTimesheetAction->execute($timesheet, $approver);
    }

    public function rejectTimesheet(Timesheet $timesheet, User $rejector, string $reason): void
    {
        $this->rejectTimesheetAction->execute($timesheet, $rejector, $reason);
    }

    /**
     * Validate a collection of lines before creation.
     *
     * @param  array<TimesheetLineDTO>  $lines
     */
    public function validateTimesheetLines(array $lines): bool
    {
        foreach ($lines as $line) {
            if ($line->hours <= 0) {
                return false;
            }
            if (! $line->project_id && ! $line->project_task_id) {
                // Must explicitly enforce project link on line?
                // DB definition has them nullable but business rule usually requires at least a project.
                // Let's assume project_id is mandatory for now.
                // Check TimesheetLine migration: $table->foreignId('project_id')->nullable();
                // So it is optional in DB, but maybe required by business logic?
                // For "Internal" tasks?
                // Let's keep it flexible for now.
            }
        }

        return true;
    }
}
