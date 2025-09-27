<?php

namespace App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockQuantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
