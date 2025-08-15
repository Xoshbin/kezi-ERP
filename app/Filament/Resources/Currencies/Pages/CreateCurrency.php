<?php

namespace App\Filament\Resources\Currencies\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\Currencies\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

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
