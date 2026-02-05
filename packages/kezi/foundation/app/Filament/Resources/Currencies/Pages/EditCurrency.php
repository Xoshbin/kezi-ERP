<?php

namespace Kezi\Foundation\Filament\Resources\Currencies\Pages;

use \Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Foundation\Filament\Resources\Currencies\CurrencyResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

/**
 * @extends EditRecord<\Kezi\Foundation\Models\Currency>
 */
class EditCurrency extends EditRecord
{
    use Translatable;

    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
