<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use \Filament\Actions\DeleteAction;
use \Filament\Actions\ForceDeleteAction;
use \Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

/**
 * @extends EditRecord<\Kezi\HR\Models\Employee>
 */
class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Kezi\HR\Models\Employee $record */
        return app(\Kezi\HR\Actions\Employees\UpdateEmployeeAction::class)
            ->execute($record, \Kezi\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('employee-management'),
        ];
    }
}
