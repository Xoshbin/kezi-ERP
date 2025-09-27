<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveTypes extends ListRecords
{
    protected static string $resource = LeaveTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
