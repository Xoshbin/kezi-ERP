<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set company_id from tenant context
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey();

        return $data;
    }
}
