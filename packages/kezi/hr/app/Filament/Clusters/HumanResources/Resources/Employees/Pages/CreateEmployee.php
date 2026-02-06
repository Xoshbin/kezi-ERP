<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

/**
 * @extends CreateRecord<\Kezi\HR\Models\Employee>
 */
class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return app(\Kezi\HR\Actions\Employees\CreateEmployeeAction::class)
            ->execute(\Kezi\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('employee-management'),
        ];
    }
}
