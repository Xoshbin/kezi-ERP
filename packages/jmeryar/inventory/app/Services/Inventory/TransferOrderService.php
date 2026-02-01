<?php

namespace Jmeryar\Inventory\Services\Inventory;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\Actions\Inventory\ReceiveTransferAction;
use Jmeryar\Inventory\Actions\Inventory\ShipTransferAction;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateTransferDTO;
use Jmeryar\Inventory\DataTransferObjects\Inventory\ReceiveTransferDTO;
use Jmeryar\Inventory\DataTransferObjects\Inventory\ShipTransferDTO;
use Jmeryar\Inventory\Enums\Inventory\StockLocationType;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Enums\Inventory\StockPickingType;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockPicking;

/**
 * Transfer Order Service
 *
 * Orchestrates the two-step inter-warehouse transfer workflow:
 * 1. Create: Draft transfer with source, transit, and destination locations
 * 2. Confirm: Reserve stock at source location
 * 3. Ship: Move stock from source to transit location
 * 4. Receive: Move stock from transit to destination location
 *
 * This service follows the Odoo/SAP pattern of using virtual transit locations
 * to track goods in-transit between warehouses.
 */
class TransferOrderService
{
    public function __construct(
        private readonly ShipTransferAction $shipTransferAction,
        private readonly ReceiveTransferAction $receiveTransferAction,
    ) {}

    /**
     * Create a new internal transfer in draft state.
     */
    public function create(CreateTransferDTO $dto): StockPicking
    {
        return DB::transaction(function () use ($dto) {
            // Create the stock picking for internal transfer
            $picking = StockPicking::create([
                'company_id' => $dto->company_id,
                'type' => StockPickingType::Internal,
                'state' => StockPickingState::Draft,
                'transit_location_id' => $dto->transit_location_id ?? $this->getDefaultTransitLocation($dto->company_id)?->id,
                'destination_location_id' => $dto->destination_location_id,
                'scheduled_date' => $dto->scheduled_date ?? Carbon::now(),
                'origin' => $dto->notes,
                'created_by_user_id' => $dto->created_by_user_id,
            ]);

            // Create stock moves for each line
            foreach ($dto->lines as $line) {
                $picking->stockMoves()->create([
                    'company_id' => $dto->company_id,
                    'move_type' => StockMoveType::InternalTransfer,
                    'status' => StockMoveStatus::Draft,
                    'move_date' => Carbon::now(),
                    'reference' => $picking->reference,
                    'created_by_user_id' => $dto->created_by_user_id,
                ])->productLines()->create([
                    'company_id' => $dto->company_id,
                    'product_id' => $line->product_id,
                    'quantity' => $line->quantity,
                    'from_location_id' => $dto->source_location_id,
                    'to_location_id' => $dto->destination_location_id,
                    'description' => $line->description,
                ]);
            }

            return $picking->load('stockMoves.productLines');
        });
    }

    /**
     * Confirm a transfer order and reserve stock.
     */
    public function confirm(StockPicking $picking, User $user): StockPicking
    {
        if (! $picking->isDraft()) {
            throw new \RuntimeException('Only draft transfers can be confirmed.');
        }

        if (! $picking->isInternalTransfer()) {
            throw new \RuntimeException('Only internal transfers can be confirmed through this service.');
        }

        return DB::transaction(function () use ($picking): StockPicking {
            // Update picking state
            $picking->update(['state' => StockPickingState::Confirmed]);

            // Confirm all stock moves
            foreach ($picking->stockMoves as $move) {
                $move->update(['status' => StockMoveStatus::Confirmed]);
            }

            // TODO: Create stock reservations for the source quantities

            /** @var StockPicking $result */
            $result = $picking->fresh();

            return $result;
        });
    }

    /**
     * Ship a transfer order (move stock to transit location).
     */
    public function ship(StockPicking $picking, ShipTransferDTO $dto, User $user): StockPicking
    {
        if (! $picking->canBeShipped()) {
            throw new \RuntimeException('Transfer cannot be shipped in its current state.');
        }

        return $this->shipTransferAction->execute($picking, $dto, $user);
    }

    /**
     * Receive a transfer order (move stock from transit to destination).
     */
    public function receive(StockPicking $picking, ReceiveTransferDTO $dto, User $user): StockPicking
    {
        if (! $picking->canBeReceived()) {
            throw new \RuntimeException('Transfer cannot be received in its current state.');
        }

        return $this->receiveTransferAction->execute($picking, $dto, $user);
    }

    /**
     * Cancel a transfer order and release any reservations.
     */
    public function cancel(StockPicking $picking, User $user): StockPicking
    {
        if ($picking->isShipped() || $picking->isDone()) {
            throw new \RuntimeException('Shipped or completed transfers cannot be cancelled.');
        }

        return DB::transaction(function () use ($picking): StockPicking {
            // Cancel all stock moves
            foreach ($picking->stockMoves as $move) {
                $move->update(['status' => StockMoveStatus::Cancelled]);
            }

            // TODO: Release stock reservations

            $picking->update(['state' => StockPickingState::Cancelled]);

            /** @var StockPicking $result */
            $result = $picking->fresh();

            return $result;
        });
    }

    /**
     * Get or create the default transit location for a company.
     */
    public function getDefaultTransitLocation(int $companyId): ?StockLocation
    {
        $transitLocation = StockLocation::where('company_id', $companyId)
            ->where('type', StockLocationType::Transit)
            ->where('is_active', true)
            ->first();

        if (! $transitLocation) {
            // Auto-create a default transit location
            $transitLocation = StockLocation::create([
                'company_id' => $companyId,
                'name' => 'In Transit',
                'type' => StockLocationType::Transit,
                'is_active' => true,
            ]);
        }

        return $transitLocation;
    }
}
