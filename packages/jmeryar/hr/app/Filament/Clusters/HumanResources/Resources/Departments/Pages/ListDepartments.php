<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            DocsAction::make('department-position-config'),
        ];
    }
}
