<?php

namespace Modules\ProjectManagement\Filament\Resources\Timesheets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\ProjectManagement\Filament\Resources\Timesheets\TimesheetResource;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
