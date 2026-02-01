<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

class ListStockQuants extends ListRecords
{
    protected static string $resource = StockQuantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-adjustments'),
            Actions\CreateAction::make(),
        ];
    }
}
