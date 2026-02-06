<?php

namespace Kezi\Foundation\Filament\Resources\CurrencyRates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Foundation\Filament\Resources\CurrencyRates\CurrencyRateResource;

/**
 * @extends EditRecord<\Kezi\Foundation\Models\CurrencyRate>
 */
class EditCurrencyRate extends EditRecord
{
    protected static string $resource = CurrencyRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
