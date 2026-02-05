<?php

namespace Kezi\Foundation\Filament\Resources\CurrencyRates\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Filament\Resources\CurrencyRates\CurrencyRateResource;

class ListCurrencyRates extends ListRecords
{
    protected static string $resource = CurrencyRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-currencies'),
            CreateAction::make(),
        ];
    }
}
