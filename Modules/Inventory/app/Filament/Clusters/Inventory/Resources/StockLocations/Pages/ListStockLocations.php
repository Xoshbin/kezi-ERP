<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;

class ListStockLocations extends ListRecords
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('inventory-management'),
            CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('inventory::stock_location.plural_label');
    }
}
