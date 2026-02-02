<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

class ListLeaveRequests extends ListRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DocsAction::make('leave-management'),
        ];
    }
}
