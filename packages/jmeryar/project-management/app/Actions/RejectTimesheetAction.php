<?php

namespace Jmeryar\ProjectManagement\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Jmeryar\ProjectManagement\Events\TimesheetRejected;
use Jmeryar\ProjectManagement\Models\Timesheet;
use RuntimeException;

class RejectTimesheetAction
{
    public function execute(Timesheet $timesheet, User $rejector, string $reason): void
    {
        if (! $timesheet->isSubmitted()) {
            throw new RuntimeException('Only submitted timesheets can be rejected.');
        }

        DB::transaction(function () use ($timesheet, $reason) {
            $timesheet->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                // We might want to clear approval info if it was re-submitted?
                // But logic says Draft -> Submitted -> Approved/Rejected.
            ]);

            TimesheetRejected::dispatch($timesheet);
        });
    }
}
