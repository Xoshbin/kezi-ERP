<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\CurrencyRates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;

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
