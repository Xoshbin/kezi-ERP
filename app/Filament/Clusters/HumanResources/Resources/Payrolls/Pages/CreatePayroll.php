<?php

namespace App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use App\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set company_id from tenant context
        /** @var \App\Models\Company|null $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey();

        return $data;
    }
}
