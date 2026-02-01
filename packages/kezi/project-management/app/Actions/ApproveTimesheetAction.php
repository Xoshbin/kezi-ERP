<?php

namespace Kezi\ProjectManagement\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Kezi\ProjectManagement\Events\TimesheetApproved;
use Kezi\ProjectManagement\Models\Timesheet;
use RuntimeException;

class ApproveTimesheetAction
{
    public function execute(Timesheet $timesheet, User $approver): void
    {
        if (! $timesheet->isSubmitted()) {
            throw new RuntimeException('Only submitted timesheets can be approved.');
        }

        DB::transaction(function () use ($timesheet, $approver) {
            $timesheet->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Task actual hours update is handled by TimesheetLineObserver when status changes to approved
            // (Wait, observer listens to TimesheetLine events? Or Timesheet events?
            // TimesheetLineObserver listens to Line changes.
            // I should prob also have TimesheetObserver listen to status change to trigger line observers?
            // OR I can explicitly update tasks here.
            // The plan said "Approve timesheet updates task actual hours".
            // In TimesheetLineObserver I implemented `updateTaskActualHours`.
            // BUT that runs on Line Updated/Deleted.
            // Does it run on Timesheet Updated? No.
            // So if I approve timesheet, lines aren't touched, so observer won't run.
            // I need to trigger it here.)

            // Trigger recalculation of task hours
            foreach ($timesheet->lines as $line) {
                if ($line->projectTask) {
                    // Force touch or manually calc
                    // Better: call the logic directly or refactor observer.
                    // Let's manually trigger the update logic here or better, dispatch an event that a listener handles?
                    // Or just iterate and update tasks. Simplest is iterate.

                    // Re-using logic from observer:
                    $task = $line->projectTask;
                    $totalHours = $task->timesheetLines()
                        ->whereHas('timesheet', function ($query) {
                            $query->where('status', 'approved');
                        })
                        ->sum('hours'); // This will include current timesheet lines now that it is approved.

                    $task->actual_hours = $totalHours;
                    $task->saveQuietly();
                }
            }

            TimesheetApproved::dispatch($timesheet);
        });
    }
}
