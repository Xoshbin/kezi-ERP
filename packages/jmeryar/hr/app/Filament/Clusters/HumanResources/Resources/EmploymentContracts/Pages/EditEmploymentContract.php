<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;

class EditEmploymentContract extends EditRecord
{
    protected static string $resource = EmploymentContractResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Jmeryar\HR\Models\EmploymentContract $record */
        $currencyCode = $record->currency->code;

        return app(\Jmeryar\HR\Actions\EmploymentContracts\UpdateEmploymentContractAction::class)
            ->execute($record, \Jmeryar\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('employment-contracts'),
        ];
    }
}
