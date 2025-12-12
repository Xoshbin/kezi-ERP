<?php

namespace Modules\Inventory\Services\Inventory;

use Carbon\Carbon;
use Modules\Inventory\DataTransferObjects\Inventory\ConfirmStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\UpdateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\UpdateStockMoveWithProductLinesDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Events\Inventory\StockMoveConfirmed;
use Modules\Inventory\Models\StockMove;

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
 * @author Laravel/Filament Inventory System
 *
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
     * @param  CreateStockMoveDTO  $dto  Data transfer object containing move details
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
        // If the move is being created already "done", defer the status change until after lines exist.
        $desiredStatus = $dto->status;
        $initialStatus = $desiredStatus === StockMoveStatus::Done ? StockMoveStatus::Draft : $desiredStatus;

        $stockMove = StockMove::create([
            'company_id' => $dto->company_id,
            'move_type' => $dto->move_type,
            'status' => $initialStatus,
            'move_date' => $dto->move_date,
            'reference' => $dto->reference,
            'description' => $dto->description,
            'source_type' => $dto->source_type,
            'source_id' => $dto->source_id,
            'created_by_user_id' => $dto->created_by_user_id,
        ]);

        // Create product lines
        foreach ($dto->product_lines as $productLineDTO) {
            $stockMove->productLines()->create([
                'company_id' => $dto->company_id,
                'product_id' => $productLineDTO->product_id,
                'quantity' => $productLineDTO->quantity,
                'from_location_id' => $productLineDTO->from_location_id,
                'to_location_id' => $productLineDTO->to_location_id,
                'description' => $productLineDTO->description,
                'source_type' => $productLineDTO->source_type,
                'source_id' => $productLineDTO->source_id,
            ]);
        }

        // Now, if the desired status was done, update it to trigger observers and posting logic.
        if ($desiredStatus === StockMoveStatus::Done) {
            $stockMove->status = StockMoveStatus::Done;
            $stockMove->save(); // Triggers 'updated' observer after lines exist
        }

        return $stockMove->load('productLines');
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

    public function updateMoveWithProductLines(StockMove $move, UpdateStockMoveWithProductLinesDTO $dto): StockMove
    {
        // 1. Update fields except status
        $move->update([
            'move_type' => $dto->move_type,
            'move_date' => $dto->move_date,
            'reference' => $dto->reference,
            'description' => $dto->description,
            'source_type' => $dto->source_type,
            'source_id' => $dto->source_id,
        ]);

        // 2. Refresh product lines
        $move->productLines()->delete();

        foreach ($dto->product_lines as $productLineDTO) {
            $move->productLines()->create([
                'company_id' => $move->company_id,
                'product_id' => $productLineDTO->product_id,
                'quantity' => $productLineDTO->quantity,
                'from_location_id' => $productLineDTO->from_location_id,
                'to_location_id' => $productLineDTO->to_location_id,
                'description' => $productLineDTO->description,
                'source_type' => $productLineDTO->source_type,
                'source_id' => $productLineDTO->source_id,
            ]);
        }

        // 3. Update status last to trigger observers with new lines
        if ($move->status !== $dto->status) {
            $move->status = $dto->status;
            $move->save();
        }

        return $move->load('productLines');
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
