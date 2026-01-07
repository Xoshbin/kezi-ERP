<?php

namespace Modules\ProjectManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ProjectManagement\Models\Timesheet;

class TimesheetRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Timesheet $timesheet) {}
}
