<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\EmploymentContracts\EmploymentContractResource;

class CreateEmploymentContract extends CreateRecord
{
    protected static string $resource = EmploymentContractResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;
        $currencyCode = \Modules\Foundation\Models\Currency::findOrFail($data['currency_id'])->code;

        return app(\Modules\HR\Actions\EmploymentContracts\CreateEmploymentContractAction::class)
            ->execute(\Modules\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO::fromArray($data, $currencyCode));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('employment-contracts'),
        ];
    }
}
