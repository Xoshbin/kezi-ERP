<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return app(\Modules\HR\Actions\Employees\CreateEmployeeAction::class)
            ->execute(\Modules\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('employee-management'),
        ];
    }
}
