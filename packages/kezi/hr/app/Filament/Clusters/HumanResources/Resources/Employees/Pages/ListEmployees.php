<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('employee-management'),
            CreateAction::make(),
        ];
    }
}
