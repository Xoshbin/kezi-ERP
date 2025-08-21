<?php

namespace App\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use App\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;
}
