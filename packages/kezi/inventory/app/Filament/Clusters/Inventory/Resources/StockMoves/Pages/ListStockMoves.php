<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;

class ListStockMoves extends ListRecords
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),

            DocsAction::make('understanding-inventory-ins-and-outs'),
            DocsAction::make('stock-movements'),
            DocsAction::make('scrap-management'),
        ];
    }

    public function getTitle(): string
    {
        return __('inventory::stock_move.plural_label');
    }
}
