<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\TimesheetResource;

class ListTimesheets extends ListRecords
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('timesheet-tracking'),
            CreateAction::make(),
        ];
    }
}
