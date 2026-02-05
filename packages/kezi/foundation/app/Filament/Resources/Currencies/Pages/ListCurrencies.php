<?php

namespace Kezi\Foundation\Filament\Resources\Currencies\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Filament\Resources\Currencies\CurrencyResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListCurrencies extends ListRecords
{
    use Translatable;

    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-currencies'),
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
