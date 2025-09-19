<?php

namespace App\Services\Inventory;

use App\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Events\Inventory\StockMoveConfirmed;
use App\Models\StockMove;

/**
 * Stock Move Service
 *
 * This service manages the lifecycle of stock movements including creation, updates,
 * confirmation, and processing. It handles all types of stock movements (receipts,
 * deliveries, transfers, adjustments) with proper status workflow management.
 *
 * Key Features:
 * - Stock move creation with validation
 * - Status workflow management (Draft → Confirmed → Done)
 * - Event-driven processing for confirmed moves
 * - DTO-based data transfer for type safety
 * - Integration with valuation and reservation services
 *
 * Movement Types:
 * - Receipt: Incoming stock from vendors or production
 * - Delivery: Outgoing stock to customers
 * - Internal Transfer: Movement between locations
 * - Adjustment: Inventory corrections and write-offs
 *
 * Status Workflow:
 * - Draft: Editable, no stock impact
 * - Confirmed: Locked, stock reserved
 * - Done: Completed, stock updated, journal entries created
 *
 * @package App\Services\Inventory
 * @author Laravel/Filament Inventory System
 * @version 1.0.0
 */
class StockMoveService
{
    /**
     * Create a new stock move from DTO
     *
     * This method creates a new stock move record using the provided data transfer object.
     * The move is created in Draft status by default, allowing for modifications before
     * confirmation and processing.
     *
     * @param CreateStockMoveDTO $dto Data transfer object containing move details
     *
     * @return StockMove The newly created stock move
     *
     * @example
     * $dto = new CreateStockMoveDTO(
     *     company_id: 1,
     *     product_id: 123,
     *     quantity: 100.0,
     *     from_location_id: 456,
     *     to_location_id: 789,
     *     move_type: StockMoveType::Receipt,
     *     move_date: Carbon::now()
     * );
     * $move = $service->createMove($dto);
     */
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
        $move->status = StockMoveStatus::Done;
        $move->save();

        StockMoveConfirmed::dispatch($move);

        return $move;
    }

    public function cancelMove(StockMove $move): StockMove
    {
        $move->status = StockMoveStatus::Cancelled;
        $move->save();

        return $move;
    }
}
