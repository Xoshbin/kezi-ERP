<?php

namespace App\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use App\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

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
