<?php

namespace Kezi\Inventory\Listeners\Sales;

use Illuminate\Support\Collection;
use Kezi\Inventory\Actions\Inventory\CreateStockMoveAction;
use Kezi\Inventory\Actions\Inventory\ValidateStockPickingAction;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockReservationService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Sales\Events\InvoiceConfirmed;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Models\InvoiceLine;

/**
 * Listener that creates stock moves when an invoice is confirmed.
 *
 * This listener decouples the Inventory module from the Sales module by
 * reacting to the InvoiceConfirmed event rather than having the Sales
 * module directly call Inventory actions.
 */
class CreateStockMovesOnInvoiceConfirmed
{
    public function __construct(
        protected CreateStockMoveAction $createStockMoveAction,
        protected StockReservationService $reservationService,
        protected ValidateStockPickingAction $validateStockPickingAction,
    ) {}

    /**
     * Handle the InvoiceConfirmed event.
     */
    public function handle(InvoiceConfirmed $event): void
    {
        $invoice = $event->invoice;

        // Only create stock moves if the invoice is not linked to a sales order
        // (sales orders handle their own deliveries)
        if ($invoice->sales_order_id) {
            return;
        }

        $this->createStockMovesForInvoice($invoice);
    }

    /**
     * Create stock moves for all storable products in an invoice.
     *
     * @return Collection<int, StockMove>
     */
    protected function createStockMovesForInvoice(Invoice $invoice): Collection
    {
        $stockMoves = collect();

        // Load invoice lines with products
        $invoice->load(['invoiceLines.product', 'company']);

        // Get stock locations with fallback strategy
        $locations = $this->getStockLocations($invoice);

        if (! $locations['warehouse'] || ! $locations['customer']) {
            // Skip stock move creation if locations are not available
            return $stockMoves;
        }

        // Create a Delivery picking to group all moves for this invoice
        // Initially in Assigned state since we will validate it
        $picking = StockPicking::create([
            'company_id' => $invoice->company_id,
            'type' => StockPickingType::Delivery,
            'state' => StockPickingState::Assigned,
            'partner_id' => $invoice->customer_id,
            'scheduled_date' => $invoice->posted_at ?? now(),
            'reference' => $invoice->invoice_number,
            'origin' => 'Invoice#'.$invoice->getKey(),
            'created_by_user_id' => $invoice->user_id ?? auth()->id() ?? 1,
        ]);

        $movesData = [];

        foreach ($invoice->invoiceLines as $line) {
            if ($line->product && $line->product->type === ProductType::Storable) {
                // Create move as Draft
                $stockMove = $this->createStockMoveForLine(
                    $invoice,
                    $line,
                    $locations['warehouse'],
                    $locations['customer']
                );

                // Attach to picking
                $stockMove->update(['picking_id' => $picking->getKey()]);

                // Reserve as much as possible from warehouse
                $reservedQty = $this->reservationService->reserveForMove($stockMove, $locations['warehouse']->id);

                // Prepare data for validation action
                // We assume the "actual" quantity is what we could reserve
                // ValidateStockPickingAction expects 'moves' array with 'move_id', 'product_line_id', 'planned_quantity', 'actual_quantity'

                // Since CreateStockMoveAction creates a product line, we need to fetch it
                $productLine = $stockMove->productLines()->first();

                if ($productLine) {
                    $movesData[] = [
                        'move_id' => $stockMove->id,
                        'product_line_id' => $productLine->id,
                        'planned_quantity' => (float) $line->quantity,
                        'actual_quantity' => $reservedQty, // The amount we actually have and are shipping now
                    ];
                }

                $stockMoves->push($stockMove);
            }
        }

        if (! empty($movesData)) {
            // Check if we have ANY actual quantity to move
            $hasActualQty = collect($movesData)->sum('actual_quantity') > 0;

            if ($hasActualQty) {
                // Validate only if we have something to move immediately
                // This validates the partial amount and creates a backorder for the rest
                $this->validateStockPickingAction->execute(
                    $picking,
                    ['moves' => $movesData],
                    true // Create backorder = true
                );
            } else {
                // If nothing was reserved, we leave the picking as Assigned (Confirmed)
                // The user can manually check availability later
                // We do NOT want to mark it as Done with 0 quantity
            }
        }

        return $stockMoves;
    }

    /**
     * Get stock locations using fallback strategy.
     *
     * @return array{warehouse: StockLocation|null, customer: StockLocation|null}
     */
    protected function getStockLocations(Invoice $invoice): array
    {
        /** @var StockLocation|null $warehouseLocation */
        $warehouseLocation = $invoice->company->defaultStockLocation
            ?? StockLocation::where('company_id', $invoice->company_id)
                ->where('type', StockLocationType::Internal)
                ->first()
            ?? StockLocation::where('name', 'Warehouse')->first();

        /** @var StockLocation|null $customerLocation */
        $customerLocation = StockLocation::where('company_id', $invoice->company_id)
            ->where('type', StockLocationType::Customer)
            ->first()
            ?? StockLocation::where('name', 'Customers')->first();

        return [
            'warehouse' => $warehouseLocation,
            'customer' => $customerLocation,
        ];
    }

    /**
     * Create a stock move for a single invoice line.
     */
    protected function createStockMoveForLine(
        Invoice $invoice,
        InvoiceLine $line,
        StockLocation $warehouseLocation,
        StockLocation $customerLocation,
    ): StockMove {
        $productLineDto = new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: (float) $line->quantity,
            from_location_id: $warehouseLocation->id,
            to_location_id: $customerLocation->id,
            description: $line->description,
            source_type: Invoice::class,
            source_id: $invoice->id
        );

        $dto = new CreateStockMoveDTO(
            company_id: $invoice->company_id,
            product_lines: [$productLineDto],
            move_type: StockMoveType::Outgoing,
            status: StockMoveStatus::Draft, // Start as Draft, let ValidateStockPickingAction handle transition
            move_date: $invoice->posted_at ?? now(),
            reference: $invoice->invoice_number,
            description: "Stock delivery for invoice {$invoice->invoice_number}",
            source_id: $invoice->id,
            source_type: Invoice::class,
            created_by_user_id: $invoice->user_id ?? auth()->id() ?? 1,
        );

        return $this->createStockMoveAction->execute($dto);
    }
}
