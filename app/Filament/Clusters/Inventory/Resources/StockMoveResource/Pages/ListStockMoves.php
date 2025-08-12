<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoveResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockMoveResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockMoves extends ListRecords
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_move.plural_label');
    }
}
