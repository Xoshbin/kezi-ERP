<?php

namespace Kezi\Inventory\Listeners\Purchase;

use Illuminate\Support\Collection;
use Kezi\Inventory\Actions\Inventory\CreateStockMoveAction;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\InventoryValuationService;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Purchase\Events\VendorBillConfirmed;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Purchase\Models\VendorBillLine;
use RuntimeException;

/**
 * Listener that creates stock moves when a vendor bill is confirmed.
 *
 * This listener decouples the Inventory module from the Purchase module by
 * reacting to the VendorBillConfirmed event rather than having the Purchase
 * module directly call Inventory actions.
 */
class CreateStockMovesOnVendorBillConfirmed
{
    public function __construct(
        protected CreateStockMoveAction $createStockMoveAction,
        protected InventoryValuationService $inventoryValuationService,
    ) {}

    /**
     * Handle the VendorBillConfirmed event.
     */
    public function handle(VendorBillConfirmed $event): void
    {
        $vendorBill = $event->vendorBill;
        $user = $event->user;

        // Load company and check inventory accounting mode
        $vendorBill->load(['company', 'lines.product']);
        $company = $vendorBill->company;

        // Only create stock moves in AUTO_RECORD_ON_BILL mode
        if ($company->inventory_accounting_mode !== InventoryAccountingMode::AUTO_RECORD_ON_BILL) {
            // Mode 2: Manual inventory recording - skip stock move creation
            return;
        }

        // Get storable lines
        $storableLines = $vendorBill->lines
            ->filter(fn (VendorBillLine $line) => $line->product?->type === ProductType::Storable);

        if ($storableLines->isEmpty()) {
            return;
        }

        $this->createStockMovesForVendorBill($vendorBill, $storableLines, $user->id);
    }

    /**
     * Create stock moves for all storable products in a vendor bill.
     */
    protected function createStockMovesForVendorBill(
        VendorBill $vendorBill,
        Collection $storableLines,
        int $userId
    ): void {
        $company = $vendorBill->company;

        if (! $company->vendorLocation || ! $company->defaultStockLocation) {
            throw new RuntimeException(
                "Default Vendor or Stock Location is not configured for Company ID: {$company->getKey()}."
            );
        }

        // Create a Receipt picking to group all moves for this bill
        $picking = StockPicking::create([
            'company_id' => $vendorBill->company_id,
            'type' => StockPickingType::Receipt,
            'state' => StockPickingState::Done,
            'partner_id' => $vendorBill->vendor_id,
            'scheduled_date' => $vendorBill->bill_date,
            'completed_at' => now(),
            'reference' => $vendorBill->bill_reference,
            'origin' => 'VendorBill#'.$vendorBill->getKey(),
            'created_by_user_id' => $userId,
        ]);

        // Create stock move with all product lines
        $stockMove = $this->createStockMoveForLines($vendorBill, $storableLines, $userId, $picking);

        // Create consolidated inventory journal entry for all storable products
        // This handles cost layer creation (FIFO/LIFO), stock quant updates, and journal entry creation
        // (Inventory Dr / Stock Input Cr) following Anglo-Saxon accounting
        // Note: We do NOT dispatch StockMoveConfirmed here because createConsolidatedIncomingStockJournalEntry
        // already fully handles valuation. Dispatching the event would cause HandleStockMoveConfirmation to
        // also process incoming stock via ProcessIncomingStockJob, creating duplicate cost layers.
        $this->inventoryValuationService->createConsolidatedIncomingStockJournalEntry(
            [$stockMove],
            $vendorBill
        );

        // Update stock quants for the incoming stock moves
        // This is necessary because we removed the automatic quant update from
        // InventoryValuationService to prevent double-counting in manual actions.
        $stockQuantService = app(\Kezi\Inventory\Services\Inventory\StockQuantService::class);
        foreach ($stockMove->productLines as $productLine) {
            $stockQuantService->applyForIncomingProductLine($productLine);
        }

        // Mark move as Done without triggering redundant event-driven flow
        $stockMove->update(['status' => StockMoveStatus::Done]);
    }

    /**
     * Creates a stock move with multiple product lines for vendor bill lines.
     */
    protected function createStockMoveForLines(
        VendorBill $vendorBill,
        Collection $storableLines,
        int $userId,
        StockPicking $picking
    ): StockMove {
        $company = $vendorBill->company;

        // Create product line DTOs for all storable lines
        $productLineDtos = [];
        foreach ($storableLines as $line) {
            $productLineDtos[] = new CreateStockMoveProductLineDTO(
                product_id: $line->product_id,
                quantity: (float) $line->quantity,
                from_location_id: $company->vendorLocation->getKey(),
                to_location_id: $company->defaultStockLocation->getKey(),
                description: $line->description,
                source_type: VendorBill::class,
                source_id: $vendorBill->getKey()
            );
        }

        $dto = new CreateStockMoveDTO(
            company_id: $company->getKey(),
            product_lines: $productLineDtos,
            move_type: StockMoveType::Incoming,
            status: StockMoveStatus::Draft,
            move_date: $vendorBill->bill_date,
            reference: $vendorBill->bill_reference,
            description: "Stock receipt from vendor bill {$vendorBill->bill_reference}",
            source_type: VendorBill::class,
            source_id: $vendorBill->getKey(),
            created_by_user_id: $userId
        );

        $stockMove = $this->createStockMoveAction->execute($dto);

        // Attach move to the picking
        $stockMove->update(['picking_id' => $picking->getKey()]);

        return $stockMove;
    }
}
