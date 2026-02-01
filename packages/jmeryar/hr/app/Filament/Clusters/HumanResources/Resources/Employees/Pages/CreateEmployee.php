<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return app(\Jmeryar\HR\Actions\Employees\CreateEmployeeAction::class)
            ->execute(\Jmeryar\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('employee-management'),
        ];
    }
}
