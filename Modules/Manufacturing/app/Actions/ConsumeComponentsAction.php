<?php

namespace Modules\Manufacturing\Actions;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Services\Inventory\StockMoveService;
use Modules\Manufacturing\Actions\Accounting\CreateJournalEntryForConsumptionAction;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ConsumeComponentsAction
{
    public function __construct(
        private readonly StockMoveService $stockMoveService,
        private readonly CreateJournalEntryForConsumptionAction $createJournalEntryForConsumptionAction,
    ) {}

    public function execute(ManufacturingOrder $mo, ?User $user = null): ManufacturingOrder
    {
        return DB::transaction(function () use ($mo, $user) {
            // Validate current status
            if ($mo->status !== ManufacturingOrderStatus::InProgress) {
                throw new \InvalidArgumentException('Only in-progress manufacturing orders can consume components.');
            }

            // Resolve user for accountability
            $currentUser = $user ?? Auth::user();
            if (! $currentUser) {
                throw new \RuntimeException('A user is required to perform component consumption and accounting entries.');
            }

            // Create stock move for component consumption
            $productLines = [];

            foreach ($mo->lines as $line) {
                if ($line->quantity_consumed < $line->quantity_required) {
                    $quantityToConsume = (float) ($line->quantity_required - $line->quantity_consumed);

                    $productLines[] = new CreateStockMoveProductLineDTO(
                        product_id: $line->product_id,
                        quantity: $quantityToConsume,
                        from_location_id: $mo->source_location_id,
                        to_location_id: $mo->destination_location_id,
                        description: "Component consumption for MO/{$mo->number}",
                        source_type: ManufacturingOrder::class,
                        source_id: $mo->id,
                    );
                }
            }

            if (! empty($productLines)) {
                $stockMoveDTO = new CreateStockMoveDTO(
                    company_id: $mo->company_id,
                    move_type: StockMoveType::InternalTransfer,
                    status: StockMoveStatus::Done,
                    move_date: Carbon::now(),
                    created_by_user_id: $currentUser->id,
                    product_lines: $productLines,
                    reference: "MO/{$mo->number}",
                    description: 'Component consumption for manufacturing order',
                    source_type: ManufacturingOrder::class,
                    source_id: $mo->id,
                );

                $stockMove = $this->stockMoveService->createMove($stockMoveDTO);

                // Create accounting entries for consumption (RM -> WIP)
                // We pass the created stock move to ensure we account for exactly what was moved
                $this->createJournalEntryForConsumptionAction->execute($mo, $stockMove, $currentUser);

                // Update MO lines with consumed quantities
                foreach ($mo->lines as $line) {
                    $line->update([
                        'quantity_consumed' => $line->quantity_required,
                        'stock_move_id' => $stockMove->id,
                    ]);
                }
            }

            return $mo->fresh(['lines']);
        });
    }
}
