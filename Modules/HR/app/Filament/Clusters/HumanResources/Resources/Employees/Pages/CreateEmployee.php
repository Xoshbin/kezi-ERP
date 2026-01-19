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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
