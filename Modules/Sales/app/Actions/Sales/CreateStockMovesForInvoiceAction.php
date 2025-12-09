<?php

namespace Modules\Sales\Actions\Sales;

use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Enums\Inventory\StockPickingType;
use Modules\Inventory\Events\Inventory\StockMoveConfirmed;
use Modules\Inventory\Models\StockLocation;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Services\Inventory\StockReservationService;
use Modules\Sales\DataTransferObjects\Sales\CreateStockMovesForInvoiceDTO;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;

class CreateStockMovesForInvoiceAction
{
    public function __construct(
        protected \Modules\Inventory\Actions\Inventory\CreateStockMoveAction $createStockMoveAction,
    ) {}

    /**
     * Create stock moves for all storable products in an invoice
     *
     * @return Collection<int, StockMove> Collection of created stock moves
     */
    public function execute(CreateStockMovesForInvoiceDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto) {
            $invoice = $dto->invoice;
            $user = $dto->user;
            $stockMoves = collect();

            // Get stock locations with fallback strategy
            $locations = $this->getStockLocations($invoice);

            if (! $locations['warehouse'] || ! $locations['customer']) {
                // Skip stock move creation if locations are not available
                return $stockMoves;
            }

            // Create a Delivery picking to group all moves for this invoice
            $picking = StockPicking::create([
                'company_id' => $invoice->company_id,
                'type' => StockPickingType::Delivery,
                'state' => StockPickingState::Done,
                'partner_id' => $invoice->customer_id,
                'scheduled_date' => $invoice->posted_at ?? now(),
                'completed_at' => now(),
                'reference' => $invoice->invoice_number,
                'origin' => 'Invoice#'.$invoice->getKey(),
                'created_by_user_id' => $user->id,
            ]);

            $reservationService = app(StockReservationService::class);

            foreach ($invoice->invoiceLines as $line) {
                if ($line->product && $line->product->type === \Modules\Product\Enums\Products\ProductType::Storable) {
                    $stockMove = $this->createStockMoveForLine(
                        $invoice,
                        $line,
                        $user,
                        $locations['warehouse'],
                        $locations['customer']
                    );

                    // Attach to picking
                    $stockMove->update(['picking_id' => $picking->getKey()]);

                    // Reserve as much as possible from warehouse before processing the move
                    $reservationService->reserveForMove($stockMove, $locations['warehouse']->id);

                    $stockMoves->push($stockMove);

                    // Dispatch the StockMoveConfirmed event to trigger valuation and outgoing processing
                    StockMoveConfirmed::dispatch($stockMove);
                }
            }

            return $stockMoves;
        });
    }

    /**
     * Get stock locations using fallback strategy
     *
     * @return array{warehouse: StockLocation|null, vendor: StockLocation|null}
     */
    protected function getStockLocations(Invoice $invoice): array
    {
        // Get stock locations - use company defaults or fallback to any available locations
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
     * Create a stock move for a single invoice line
     */
    protected function createStockMoveForLine(
        Invoice $invoice,
        InvoiceLine $line,
        User $user,
        StockLocation $warehouseLocation,
        StockLocation $customerLocation,
    ): StockMove {
        if (! $line->product_id) {
            throw new Exception('Invoice line must have a product to create stock move');
        }

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
            status: StockMoveStatus::Done,
            move_date: $invoice->posted_at ?? now(),
            reference: $invoice->invoice_number,
            description: "Stock delivery for invoice {$invoice->invoice_number}",
            source_id: $invoice->id,
            source_type: Invoice::class,
            created_by_user_id: $user->id,
        );

        return $this->createStockMoveAction->execute($dto);
    }
}
