<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

class ViewStockQuant extends ViewRecord
{
    protected static string $resource = StockQuantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
