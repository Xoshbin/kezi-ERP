<?php

namespace Kezi\Pos\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Pos\Models\PosOrder;
use Kezi\Product\Enums\Products\ProductType;

class DeductPosOrderStockAction
{
    public function __construct(
        protected StockMoveService $stockMoveService,
    ) {}

    public function execute(PosOrder $order): void
    {
        // 1. Resolve the source location from the POS profile
        $session = $order->session()->with('profile')->first();
        if (! $session || ! $session->profile) {
            Log::warning("POS stock deduction skipped: No session/profile for order {$order->id}");

            return;
        }

        $fromLocationId = $session->profile->stock_location_id;
        if (! $fromLocationId) {
            Log::warning("POS stock deduction skipped: POS profile '{$session->profile->name}' has no stock location set");

            return;
        }

        // 2. Resolve the "customer" location (destination for outgoing goods)
        //    Use the company's default customer location
        $customerLocation = StockLocation::where('company_id', $order->company_id)
            ->where('type', StockLocationType::Customer)
            ->first();

        if (! $customerLocation) {
            Log::warning("POS stock deduction skipped: No customer location found for company {$order->company_id}");

            return;
        }

        // 3. Build product lines from order lines
        $order->loadMissing('lines.product');
        $productLines = [];

        foreach ($order->lines as $line) {
            if ($line->product?->type !== ProductType::Storable) {
                continue;
            }

            $productLines[] = new CreateStockMoveProductLineDTO(
                product_id: $line->product_id,
                quantity: (float) $line->quantity,
                from_location_id: $fromLocationId,
                to_location_id: $customerLocation->id,
                description: "POS Sale: {$order->order_number}",
                source_type: PosOrder::class,
                source_id: $order->id,
            );
        }

        if (empty($productLines)) {
            return;
        }

        // 4. Create the stock move with status Done
        $dto = new CreateStockMoveDTO(
            company_id: $order->company_id,
            move_type: StockMoveType::Outgoing,
            status: StockMoveStatus::Done,
            move_date: $order->ordered_at ? Carbon::parse($order->ordered_at) : now(),
            created_by_user_id: $session->user_id,
            product_lines: $productLines,
            reference: "POS-{$order->order_number}",
            description: "Stock deduction for POS order {$order->order_number}",
            source_type: PosOrder::class,
            source_id: (int) $order->id,
        );

        $this->stockMoveService->createMove($dto);

        Log::info("POS stock deduction created for order {$order->order_number}", [
            'order_id' => $order->id,
            'stock_move_reference' => "POS-{$order->order_number}",
            'lines_count' => count($productLines),
        ]);
    }
}
