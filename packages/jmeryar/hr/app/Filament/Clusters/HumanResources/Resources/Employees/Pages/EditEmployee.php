<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Employees\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Employees\EmployeeResource;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Jmeryar\HR\Models\Employee $record */
        return app(\Jmeryar\HR\Actions\Employees\UpdateEmployeeAction::class)
            ->execute($record, \Jmeryar\HR\DataTransferObjects\Employees\EmployeeDTO::fromArray($data));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('employee-management'),
        ];
    }
}
