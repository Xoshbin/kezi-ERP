<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

class ListStockQuants extends ListRecords
{
    protected static string $resource = StockQuantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('inventory-adjustments'),
            Actions\CreateAction::make(),
        ];
    }
}
