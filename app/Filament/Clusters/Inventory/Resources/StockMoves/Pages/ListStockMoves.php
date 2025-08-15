<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockMoves extends ListRecords
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_move.plural_label');
    }
}
