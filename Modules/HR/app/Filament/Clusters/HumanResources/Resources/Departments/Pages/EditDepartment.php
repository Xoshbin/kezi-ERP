<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;

class EditDepartment extends EditRecord
{
    use Translatable;

    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
