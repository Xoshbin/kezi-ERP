<?php

namespace App\Filament\Clusters\Inventory\Resources\StockMoves\Pages;

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Filament\Clusters\Inventory\Resources\StockMoves\StockMoveResource;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockMove extends CreateRecord
{
    protected static string $resource = StockMoveResource::class;

    public function getTitle(): string
    {
        return __('stock_move.create_title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateStockMoveDTO(
            company_id: $data['company_id'],
            product_id: $data['product_id'],
            quantity: $data['quantity'],
            from_location_id: $data['from_location_id'],
            to_location_id: $data['to_location_id'],
            move_type: StockMoveType::from($data['move_type']),
            status: StockMoveStatus::from($data['status']),
            move_date: Carbon::parse($data['move_date']),
            created_by_user_id: Filament::auth()->id(),
            reference: $data['reference'] ?? null,
        );

        return app(CreateStockMoveAction::class)->execute($dto);
    }
}
