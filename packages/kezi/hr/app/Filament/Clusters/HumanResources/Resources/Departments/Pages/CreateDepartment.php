<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Departments\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Departments\DepartmentResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

/**
 * @extends CreateRecord<\Kezi\HR\Models\Department>
 */
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
