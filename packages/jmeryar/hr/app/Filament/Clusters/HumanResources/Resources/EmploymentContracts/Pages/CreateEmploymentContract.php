<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;

class CreateEmploymentContract extends CreateRecord
{
    protected static string $resource = EmploymentContractResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;
        $currencyCode = \Jmeryar\Foundation\Models\Currency::findOrFail($data['currency_id'])->code;

        return app(\Jmeryar\HR\Actions\EmploymentContracts\CreateEmploymentContractAction::class)
            ->execute(\Jmeryar\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('employment-contracts'),
        ];
    }
}
