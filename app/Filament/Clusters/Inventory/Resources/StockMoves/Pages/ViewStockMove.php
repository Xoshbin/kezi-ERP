<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use App\Enums\Inventory\StockMoveStatus;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMove extends ViewRecord
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => ($this->getRecord() instanceof \App\Models\StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => ($this->getRecord() instanceof \App\Models\StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $reference = $record->reference ?? $record->id ?? '';
        return __('stock_move.view_title', ['reference' => $reference]);
    }
}
