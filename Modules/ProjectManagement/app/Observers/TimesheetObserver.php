<?php

namespace Modules\ProjectManagement\Observers;

use Modules\ProjectManagement\Models\Timesheet;

class TimesheetObserver
{
    /**
     * Handle the Timesheet "created" event.
     */
    public function created(Timesheet $timesheet): void
    {
        //
    }

    /**
     * Handle the Timesheet "updated" event.
     */
    public function updated(Timesheet $timesheet): void
    {
        //
    }

    /**
     * Handle the Timesheet "deleted" event.
     */
    public function deleted(Timesheet $timesheet): void
    {
        //
    }
}
