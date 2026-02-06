<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\TimesheetResource;

/**
 * @extends EditRecord<\Kezi\ProjectManagement\Models\Timesheet>
 */
class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
