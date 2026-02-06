<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;

/**
 * @extends EditRecord<\Kezi\HR\Models\EmploymentContract>
 */
class EditEmploymentContract extends EditRecord
{
    protected static string $resource = EmploymentContractResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Kezi\HR\Models\EmploymentContract $record */
        $currencyCode = $record->currency->code;

        return app(\Kezi\HR\Actions\EmploymentContracts\UpdateEmploymentContractAction::class)
            ->execute($record, \Kezi\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('employment-contracts'),
        ];
    }
}
