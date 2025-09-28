<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Pages;


use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Modules\Inventory\Models\StockMove;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockMoves\Actions\ConfirmStockMoveAction;

class ViewStockMove extends ViewRecord
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->visible(fn(): bool => ($this->getRecord() instanceof StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
            ConfirmStockMoveAction::make()
                ->visible(fn(): bool => ($this->getRecord() instanceof StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn(): bool => ($this->getRecord() instanceof StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $reference = $record->reference ?? $record->id ?? '';

        return __('stock_move.view_title', ['reference' => $reference]);
    }
}
