<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;

class ListLeaveTypes extends ListRecords
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('leave-management'),
            CreateAction::make(),
        ];
    }
}
