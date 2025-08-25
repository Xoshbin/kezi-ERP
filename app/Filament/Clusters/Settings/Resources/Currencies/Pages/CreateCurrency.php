<?php

namespace App\Filament\Clusters\Settings\Resources\Currencies\Pages;

use App\Filament\Clusters\Settings\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateCurrency extends CreateRecord
{
    use Translatable;

    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
