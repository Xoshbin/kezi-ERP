<?php

namespace App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages;

use App\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

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
