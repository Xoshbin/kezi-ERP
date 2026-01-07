<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\ProjectManagement\Filament\Resources\Timesheets\TimesheetResource;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;
}
