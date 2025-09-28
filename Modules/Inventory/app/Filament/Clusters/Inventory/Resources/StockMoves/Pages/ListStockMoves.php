<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;

class ListStockMoves extends ListRecords
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
            DocsAction::make('stock-management'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_move.plural_label');
    }
}
