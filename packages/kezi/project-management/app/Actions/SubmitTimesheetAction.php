<?php

namespace Kezi\ProjectManagement\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\ProjectManagement\Events\TimesheetSubmitted;
use Kezi\ProjectManagement\Models\Timesheet;
use RuntimeException;

class SubmitTimesheetAction
{
    public function execute(Timesheet $timesheet): void
    {
        if (! $timesheet->isDraft()) {
            throw new RuntimeException('Only draft timesheets can be submitted.');
        }

        DB::transaction(function () use ($timesheet) {
            // Validate lines (e.g., ensure projects key are valid - usually handled by UI/Validation layer but good to have safeguard)
            if ($timesheet->lines()->count() === 0) {
                throw new RuntimeException('Cannot submit an empty timesheet.');
            }

            $timesheet->update([
                'status' => 'submitted',
            ]);

            TimesheetSubmitted::dispatch($timesheet);
        });
    }
}
