<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
