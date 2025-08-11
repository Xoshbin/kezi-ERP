<?php

namespace App\Filament\Resources\StockMoveResource\Pages;

use App\Enums\Inventory\StockMoveStatus;
use App\Filament\Resources\StockMoveResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMove extends ViewRecord
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->record->status === StockMoveStatus::Draft),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => $this->record->status === StockMoveStatus::Draft),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_move.view_title', ['reference' => $this->record->reference ?? $this->record->id]);
    }
}
