<?php

namespace App\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use App\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateDepartment extends CreateRecord
{
    use Translatable;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
