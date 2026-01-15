<?php

namespace Modules\Foundation\Filament\Resources\Currencies\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Resources\Currencies\CurrencyResource;

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
