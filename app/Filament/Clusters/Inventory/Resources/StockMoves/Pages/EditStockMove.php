<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use App\Actions\Inventory\UpdateStockMoveAction;
use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStockMove extends EditRecord
{
    protected static string $resource = StockMoveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => ($this->getRecord() instanceof \App\Models\StockMove) && $this->getRecord()->status === StockMoveStatus::Draft),
            DocsAction::make('stock-management'),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $reference = $record->reference ?? $record->id ?? '';
        return __('stock_move.edit_title', ['reference' => $reference]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $dto = new UpdateStockMoveDTO(
            id: (int) $record->getKey(),
            company_id: $data['company_id'],
            product_id: $data['product_id'],
            quantity: $data['quantity'],
            from_location_id: $data['from_location_id'],
            to_location_id: $data['to_location_id'],
            move_type: StockMoveType::from($data['move_type']),
            status: StockMoveStatus::from($data['status']),
            move_date: Carbon::parse($data['move_date']),
            reference: $data['reference'] ?? null,
        );

        return app(UpdateStockMoveAction::class)->execute($dto);
    }
}
