<?php

namespace Kezi\Foundation\Filament\Resources\Currencies\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Foundation\Filament\Resources\Currencies\CurrencyResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

/**
 * @extends CreateRecord<\Kezi\Foundation\Models\Currency>
 */
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
