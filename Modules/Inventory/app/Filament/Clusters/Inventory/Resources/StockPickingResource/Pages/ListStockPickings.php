<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;

class ListStockPickings extends ListRecords
{
    protected static string $resource = StockPickingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
            \Modules\Foundation\Filament\Actions\DocsAction::make('stock-picking'),
            \Modules\Foundation\Filament\Actions\DocsAction::make('inter-warehouse-transfers'),
        ];
    }
}
