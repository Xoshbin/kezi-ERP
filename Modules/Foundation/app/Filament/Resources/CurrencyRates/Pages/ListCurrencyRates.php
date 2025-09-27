<?php

namespace App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages;

use App\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurrencyRates extends ListRecords
{
    protected static string $resource = CurrencyRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
