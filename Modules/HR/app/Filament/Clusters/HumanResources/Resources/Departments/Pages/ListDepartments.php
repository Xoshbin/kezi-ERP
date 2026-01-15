<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;

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
