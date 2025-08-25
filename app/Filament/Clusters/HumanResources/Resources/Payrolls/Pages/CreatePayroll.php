<?php

namespace App\Filament\Clusters\HumanResources\Resources\Payrolls\Pages;

use App\Filament\Clusters\HumanResources\Resources\Payrolls\PayrollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set company_id from tenant context
        $data['company_id'] = \Filament\Facades\Filament::getTenant()?->id;

        return $data;
    }
}
