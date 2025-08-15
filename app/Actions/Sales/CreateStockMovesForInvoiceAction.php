<?php

namespace App\Actions\Sales;

use App\Enums\Inventory\StockLocationType;
use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\DataTransferObjects\Sales\CreateStockMovesForInvoiceDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Products\ProductType;
use App\Events\Inventory\StockMoveConfirmed;
use App\Models\Invoice;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateStockMovesForInvoiceAction
{
    public function __construct(
        protected CreateStockMoveAction $createStockMoveAction
    ) {
    }

    /**
     * Create stock moves for all storable products in an invoice
     *
     * @param CreateStockMovesForInvoiceDTO $dto
     * @return Collection<StockMove> Collection of created stock moves
     */
    public function execute(CreateStockMovesForInvoiceDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto) {
            $invoice = $dto->invoice;
            $user = $dto->user;
            $stockMoves = collect();

            // Get stock locations with fallback strategy
            $locations = $this->getStockLocations($invoice);

            if (!$locations['warehouse'] || !$locations['vendor']) {
                // Skip stock move creation if locations are not available
                return $stockMoves;
            }

            foreach ($invoice->invoiceLines as $line) {
                if ($line->product && $line->product->type === ProductType::Storable) {
                    $stockMove = $this->createStockMoveForLine(
                        $invoice,
                        $line,
                        $user,
                        $locations['warehouse'],
                        $locations['vendor']
                    );

                    $stockMoves->push($stockMove);

                    // Dispatch the StockMoveConfirmed event to trigger COGS calculation
                    StockMoveConfirmed::dispatch($stockMove);
                }
            }

            return $stockMoves;
        });
    }

    /**
     * Get stock locations using fallback strategy
     */
    protected function getStockLocations(Invoice $invoice): array
    {
        // Get stock locations - use company defaults or fallback to any available locations
        $warehouseLocation = $invoice->company->defaultStockLocation
            ?? StockLocation::where('company_id', $invoice->company_id)
                ->where('type', StockLocationType::Internal)
                ->first()
            ?? StockLocation::where('name', 'Warehouse')->first();

        $vendorLocation = $invoice->company->vendorLocation
            ?? StockLocation::where('company_id', $invoice->company_id)
                ->where('type', StockLocationType::Vendor)
                ->first()
            ?? StockLocation::where('name', 'Vendors')->first();

        return [
            'warehouse' => $warehouseLocation,
            'vendor' => $vendorLocation,
        ];
    }

    /**
     * Create a stock move for a single invoice line
     */
    protected function createStockMoveForLine(
        Invoice $invoice,
        $line,
        User $user,
        StockLocation $warehouseLocation,
        StockLocation $vendorLocation
    ) {
        $dto = new CreateStockMoveDTO(
            company_id: $invoice->company_id,
            product_id: $line->product_id,
            quantity: $line->quantity,
            from_location_id: $warehouseLocation->id,
            to_location_id: $vendorLocation->id,
            move_type: StockMoveType::Outgoing,
            status: StockMoveStatus::Done,
            move_date: $invoice->posted_at ?? now(),
            reference: $invoice->invoice_number,
            source_id: $invoice->id,
            source_type: Invoice::class,
            created_by_user_id: $user->id,
        );

        return $this->createStockMoveAction->execute($dto);
    }
}
