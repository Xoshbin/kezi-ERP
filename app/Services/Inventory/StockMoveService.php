<?php

namespace App\Services\Inventory;

use App\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Events\Inventory\StockMoveConfirmed;
use App\Models\StockMove;

class StockMoveService
{
    public function createMove(CreateStockMoveDTO $dto): StockMove
    {
        return StockMove::create([
            'company_id' => $dto->company_id,
            'product_id' => $dto->product_id,
            'quantity' => $dto->quantity,
            'from_location_id' => $dto->from_location_id,
            'to_location_id' => $dto->to_location_id,
            'move_type' => $dto->move_type,
            'status' => $dto->status,
            'move_date' => $dto->move_date,
            'reference' => $dto->reference,
            'source_type' => $dto->source_type,
            'source_id' => $dto->source_id,
            'created_by_user_id' => $dto->created_by_user_id,
        ]);
    }

    public function updateMove(StockMove $move, UpdateStockMoveDTO $dto): StockMove
    {
        $move->update([
            'company_id' => $dto->company_id,
            'product_id' => $dto->product_id,
            'quantity' => $dto->quantity,
            'from_location_id' => $dto->from_location_id,
            'to_location_id' => $dto->to_location_id,
            'move_type' => $dto->move_type,
            'status' => $dto->status,
            'move_date' => $dto->move_date,
            'reference' => $dto->reference,
        ]);

        return $move;
    }

    public function confirmMove(ConfirmStockMoveDTO $dto): StockMove
    {
        $move = StockMove::findOrFail($dto->stock_move_id);
        $move->status = StockMoveStatus::DONE;
        $move->save();

        StockMoveConfirmed::dispatch($move);

        return $move;
    }

    public function cancelMove(StockMove $move): StockMove
    {
        $move->status = StockMoveStatus::CANCELLED;
        $move->save();

        return $move;
    }
}
