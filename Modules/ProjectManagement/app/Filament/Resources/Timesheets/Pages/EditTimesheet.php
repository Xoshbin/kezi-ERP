<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\ProjectManagement\Filament\Resources\Timesheets\TimesheetResource;

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
