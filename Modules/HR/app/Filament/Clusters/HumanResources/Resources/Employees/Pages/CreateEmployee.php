<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
