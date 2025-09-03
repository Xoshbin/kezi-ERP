<?php

namespace App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages;

use App\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrencyRate extends CreateRecord
{
    protected static string $resource = CurrencyRateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Filament::getTenant()->id;

        return $data;
    }
}
