<?php

namespace Jmeryar\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Jmeryar\Inventory\Enums\Inventory\StockMoveStatus;
use Jmeryar\Inventory\Enums\Inventory\StockPickingState;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Inventory\Models\StockMoveProductLine;
use Jmeryar\Inventory\Models\StockPicking;
use Jmeryar\Inventory\Services\Inventory\StockReservationService;
use Jmeryar\QualityControl\Enums\QualityCheckStatus;

class ValidateStockPickingAction
{
    public function __construct(
        private readonly StockReservationService $reservationService
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(StockPicking $picking, array $data, bool $createBackorder): StockPicking
    {
        // 1. Quality Control Gate
        $this->enforceQualityGate($picking);

        return DB::transaction(function () use ($picking, $data, $createBackorder) {
            // 2. Prepare Backorder Data
            $backorderItems = [];
            foreach ($data['moves'] as $moveData) {
                $planned = (float) ($moveData['planned_quantity'] ?? 0);
                $actual = (float) ($moveData['actual_quantity'] ?? 0);

                if ($actual < $planned) {
                    $backorderItems[] = [
                        'move_id' => $moveData['move_id'],
                        'product_line_id' => $moveData['product_line_id'],
                        'planned' => $planned,
                        'actual' => $actual,
                        'backorder_qty' => $planned - $actual,
                    ];
                }
            }

            // 3. Create Backorder if requested AND needed
            if ($createBackorder && count($backorderItems) > 0) {
                $this->createBackorder($picking, $backorderItems);
            }

            // 4. Update Original Picking Lines to Actual
            $processedMoveIds = [];
            foreach ($data['moves'] as $moveData) {
                $move = StockMove::find($moveData['move_id']);
                if (! $move) {
                    continue;
                }

                $line = StockMoveProductLine::find($moveData['product_line_id']);
                if (! $line) {
                    continue;
                }

                $actualQty = (float) $moveData['actual_quantity'];

                // Update line quantity to what was actually fulfilled
                $line->update(['quantity' => $actualQty]);

                // Mark Move as Done
                if (! in_array($move->id, $processedMoveIds)) {
                    $move->update(['status' => StockMoveStatus::Done]);
                    $this->reservationService->consumeForMove($move);
                    $processedMoveIds[] = $move->id;
                }
            }

            // 5. Mark Picking as Done
            $picking->update([
                'state' => StockPickingState::Done,
                'completed_at' => now(),
            ]);

            return $picking->fresh();
        });
    }

    private function enforceQualityGate(StockPicking $picking): void
    {
        $mandatoryChecks = $picking->qualityChecks()->where('is_blocking', true)->get();

        if ($mandatoryChecks->isEmpty()) {
            return;
        }

        $notPassedChecks = $mandatoryChecks->filter(fn ($check) => $check->status !== QualityCheckStatus::Passed);

        if ($notPassedChecks->isNotEmpty()) {
            throw ValidationException::withMessages([
                'quality_checks' => __('qualitycontrol::check.quality_gate_failed'),
            ]);
        }
    }

    private function createBackorder(StockPicking $picking, array $backorderItems): void
    {
        $backorderPicking = StockPicking::create([
            'company_id' => $picking->company_id,
            'type' => $picking->type,
            'state' => StockPickingState::Assigned,
            'partner_id' => $picking->partner_id,
            'scheduled_date' => now(),
            'origin' => $picking->reference.' (Backorder)',
            'created_by_user_id' => Auth::id(),
            'reference' => $picking->reference.'-BO-'.rand(100, 999),
        ]);

        $backorderMoves = [];

        foreach ($backorderItems as $item) {
            $originalMove = StockMove::find($item['move_id']);
            $originalLine = StockMoveProductLine::find($item['product_line_id']);

            // Reuse or Create Backorder Move
            if (! isset($backorderMoves[$originalMove->id])) {
                $newMove = $originalMove->replicate();
                $newMove->picking_id = $backorderPicking->id;
                $newMove->status = StockMoveStatus::Draft;
                $newMove->save();
                $backorderMoves[$originalMove->id] = $newMove;
            }

            $newMove = $backorderMoves[$originalMove->id];

            // Create Backorder Line
            $newLine = $originalLine->replicate();
            $newLine->stock_move_id = $newMove->id;
            $newLine->quantity = $item['backorder_qty'];
            $newLine->save();
        }
    }
}
