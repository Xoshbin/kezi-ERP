<?php

namespace Jmeryar\ProjectManagement\Observers;

use Jmeryar\ProjectManagement\Models\TimesheetLine;

class TimesheetLineObserver
{
    /**
     * Handle the TimesheetLine "created" event.
     */
    public function created(TimesheetLine $timesheetLine): void
    {
        $this->updateTimesheetTotals($timesheetLine);
        $this->updateTaskActualHours($timesheetLine);
    }

    /**
     * Handle the TimesheetLine "updated" event.
     */
    public function updated(TimesheetLine $timesheetLine): void
    {
        $this->updateTimesheetTotals($timesheetLine);
        $this->updateTaskActualHours($timesheetLine);
    }

    /**
     * Handle the TimesheetLine "deleted" event.
     */
    public function deleted(TimesheetLine $timesheetLine): void
    {
        $this->updateTimesheetTotals($timesheetLine);
        $this->updateTaskActualHours($timesheetLine);
    }

    /**
     * Update parent timesheet total hours.
     */
    protected function updateTimesheetTotals(TimesheetLine $timesheetLine): void
    {
        $timesheet = $timesheetLine->timesheet;

        if ($timesheet) {
            $timesheet->recalculateTotalHours();
        }
    }

    /**
     * Update project task actual hours if timesheet is approved.
     */
    protected function updateTaskActualHours(TimesheetLine $timesheetLine): void
    {
        $timesheet = $timesheetLine->timesheet;
        $task = $timesheetLine->projectTask;

        if ($task && $timesheet && $timesheet->isApproved()) {
            $totalHours = $task->timesheetLines()
                ->whereHas('timesheet', function ($query) {
                    $query->where('status', 'approved');
                })
                ->sum('hours');

            $task->actual_hours = $totalHours;
            $task->saveQuietly();
        }
    }
}
