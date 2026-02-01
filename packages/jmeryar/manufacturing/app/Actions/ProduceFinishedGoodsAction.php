<?php

namespace Jmeryar\Manufacturing\Actions;

use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockMoveType;
use Jmeryar\Inventory\Services\Inventory\StockMoveService;
use Jmeryar\Manufacturing\Enums\ManufacturingOrderStatus;
use Jmeryar\Manufacturing\Models\ManufacturingOrder;

class ProduceFinishedGoodsAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
    ) {}

    public function execute(ManufacturingOrder $mo, ?User $user = null): ManufacturingOrder
    {
        /** @var ManufacturingOrder */
        return DB::transaction(function () use ($mo, $user) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::InProgress) {
                throw new \InvalidArgumentException('Only in-progress manufacturing orders can produce finished goods.');
            }

            // Resolve user for accountability
            $currentUser = $user ?? Auth::user();
            if (! $currentUser) {
                throw new \RuntimeException('A user is required to record production finished goods.');
            }

            // Quality Control Gate
            $mandatoryChecks = $mo->qualityChecks()->where('is_blocking', true)->get();

            if ($mandatoryChecks->isNotEmpty()) {
                $pendingChecks = $mandatoryChecks->filter(fn ($check) => $check->isPending());
                if ($pendingChecks->isNotEmpty()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quality_checks' => 'All mandatory quality checks must be completed.',
                    ]);
                }

                $failedChecks = $mandatoryChecks->filter(fn ($check) => $check->isFailed());
                if ($failedChecks->isNotEmpty()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'quality_checks' => 'All mandatory quality checks must be passed.',
                    ]);
                }
            }

            // Calculate actual production cost (sum of consumed components)
            $firstLine = $mo->lines->first();
            if (! $firstLine) {
                // Should not happen for a valid MO, but safe to handle
                throw new \RuntimeException("Manufacturing Order {$mo->number} has no lines to process.");
            }
            $totalCost = Money::zero($firstLine->currency_code);

            foreach ($mo->lines as $line) {
                // unit_cost is cast to Money via BaseCurrencyMoneyCast
                /** @var Money $unitCost */
                $unitCost = $line->unit_cost;
                $lineCost = $unitCost->multipliedBy($line->quantity_consumed);
                $totalCost = $totalCost->plus($lineCost);
            }

            // Calculate unit cost for finished product
            $unitCost = $totalCost->dividedBy($mo->quantity_to_produce, roundingMode: \Brick\Math\RoundingMode::HALF_UP);

            // Create stock move for finished goods receipt
            $stockMoveDTO = new CreateStockMoveDTO(
                company_id: $mo->company_id,
                move_type: StockMoveType::Incoming, // Production output is a receipt into stock
                status: StockMoveStatus::Done,
                move_date: Carbon::now(),
                created_by_user_id: $currentUser->id,
                product_lines: [
                    new CreateStockMoveProductLineDTO(
                        product_id: $mo->product_id,
                        quantity: $mo->quantity_to_produce,
                        from_location_id: $mo->source_location_id, // Virtual production location
                        to_location_id: $mo->destination_location_id, // Finished goods warehouse
                        description: "Production of MO/{$mo->number}",
                        source_type: ManufacturingOrder::class,
                        source_id: $mo->id
                    ),
                ],
                reference: "MO/{$mo->number}",
                source_type: ManufacturingOrder::class,
                source_id: $mo->id
            );

            // Use createMove which handles auto-confirmation if status is Done
            $stockMove = $this->stockMoveService->createMove($stockMoveDTO);

            // Update MO status
            $mo->update([
                'status' => ManufacturingOrderStatus::Done,
                'quantity_produced' => $mo->quantity_to_produce,
                'actual_end_date' => Carbon::now(),
            ]);

            // Complete work orders
            $mo->workOrders()->where('status', '!=', 'done')->update([
                'status' => 'done',
                'completed_at' => Carbon::now(),
            ]);

            return $mo->fresh();
        });
    }
}
