<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\CurrencyRates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Foundation\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;

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
