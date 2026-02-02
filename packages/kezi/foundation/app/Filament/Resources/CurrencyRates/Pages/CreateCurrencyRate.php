<?php

namespace Kezi\Foundation\Filament\Resources\CurrencyRates\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Kezi\Foundation\Filament\Resources\CurrencyRates\CurrencyRateResource;

class CreateCurrencyRate extends CreateRecord
{
    protected static string $resource = CurrencyRateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }
}
