<?php

namespace App\Services\Inventory;

use App\Actions\Inventory\CreateStockMoveAction;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Models\Company;
use App\Models\StockMove;
use App\Models\StockLocation;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterCompanyStockTransferService
{
    public function __construct(
        private readonly CreateStockMoveAction $createStockMoveAction,
        private readonly LockDateService $lockDateService,
        private readonly InventoryValuationService $inventoryValuationService
    ) {}

    /**
     * Creates a corresponding stock move (receipt) in the partner company's books
     * when a stock move (delivery) is created to a partner linked to another company.
     */
    public function createReceiptFromDelivery(StockMove $sourceStockMove, Company $targetCompany): void
    {
        // Validate that this is an outgoing move to an inter-company location
        if ($sourceStockMove->move_type !== StockMoveType::Outgoing) {
            return;
        }

        // Check if the destination location belongs to a linked company
        $destinationLocation = $sourceStockMove->toLocation;
        if (!$this->isInterCompanyLocation($destinationLocation, $targetCompany)) {
            return;
        }

        DB::transaction(function () use ($sourceStockMove, $targetCompany) {
            // Enforce lock date for the target company
            $this->lockDateService->enforce($targetCompany, $sourceStockMove->move_date);

            // Find appropriate locations in the target company
            $locations = $this->getTargetCompanyLocations($targetCompany);
            
            if (!$locations['vendor'] || !$locations['warehouse']) {
                Log::warning("Cannot create inter-company stock receipt: missing locations in target company {$targetCompany->id}");
                return;
            }

            // Create the corresponding receipt in the target company
            $receiptDTO = new CreateStockMoveDTO(
                company_id: $targetCompany->id,
                product_id: $sourceStockMove->product_id,
                quantity: $sourceStockMove->quantity,
                from_location_id: $locations['vendor']->id, // From vendor location (representing source company)
                to_location_id: $locations['warehouse']->id, // To warehouse location
                move_type: StockMoveType::Incoming,
                status: StockMoveStatus::Done, // Inter-company transfers are immediately done
                move_date: $sourceStockMove->move_date,
                reference: "IC-TRANSFER-{$sourceStockMove->id}", // Audit trail
                source_type: StockMove::class,
                source_id: $sourceStockMove->id,
                created_by_user_id: Auth::id(),
            );

            $receiptMove = $this->createStockMoveAction->execute($receiptDTO);

            // Process the incoming stock for inventory valuation
            $this->processInterCompanyIncomingStock($receiptMove, $sourceStockMove);

            Log::info("Created inter-company stock receipt {$receiptMove->id} in company {$targetCompany->id} from delivery {$sourceStockMove->id}");
        });
    }

    /**
     * Creates a corresponding stock move (delivery) in the partner company's books
     * when a stock move (receipt) is created from a partner linked to another company.
     */
    public function createDeliveryFromReceipt(StockMove $sourceStockMove, Company $targetCompany): void
    {
        // Validate that this is an incoming move from an inter-company location
        if ($sourceStockMove->move_type !== StockMoveType::Incoming) {
            return;
        }

        // Check if the source location belongs to a linked company
        $sourceLocation = $sourceStockMove->fromLocation;
        if (!$this->isInterCompanyLocation($sourceLocation, $targetCompany)) {
            return;
        }

        DB::transaction(function () use ($sourceStockMove, $targetCompany) {
            // Enforce lock date for the target company
            $this->lockDateService->enforce($targetCompany, $sourceStockMove->move_date);

            // Find appropriate locations in the target company
            $locations = $this->getTargetCompanyLocations($targetCompany);
            
            if (!$locations['warehouse'] || !$locations['customer']) {
                Log::warning("Cannot create inter-company stock delivery: missing locations in target company {$targetCompany->id}");
                return;
            }

            // Create the corresponding delivery in the target company
            $deliveryDTO = new CreateStockMoveDTO(
                company_id: $targetCompany->id,
                product_id: $sourceStockMove->product_id,
                quantity: $sourceStockMove->quantity,
                from_location_id: $locations['warehouse']->id, // From warehouse location
                to_location_id: $locations['customer']->id, // To customer location (representing target company)
                move_type: StockMoveType::Outgoing,
                status: StockMoveStatus::Done, // Inter-company transfers are immediately done
                move_date: $sourceStockMove->move_date,
                reference: "IC-TRANSFER-{$sourceStockMove->id}", // Audit trail
                source_type: StockMove::class,
                source_id: $sourceStockMove->id,
                created_by_user_id: Auth::id(),
            );

            $deliveryMove = $this->createStockMoveAction->execute($deliveryDTO);

            // Process the outgoing stock for inventory valuation
            $this->processInterCompanyOutgoingStock($deliveryMove);

            Log::info("Created inter-company stock delivery {$deliveryMove->id} in company {$targetCompany->id} from receipt {$sourceStockMove->id}");
        });
    }

    /**
     * Check if a stock location represents an inter-company relationship
     */
    private function isInterCompanyLocation(StockLocation $location, Company $targetCompany): bool
    {
        // This is a simplified check - in a real implementation, you might have
        // specific location types or naming conventions for inter-company locations
        // For now, we'll check if the location belongs to a different company
        return $location->company_id !== $targetCompany->id;
    }

    /**
     * Get appropriate stock locations for the target company
     */
    private function getTargetCompanyLocations(Company $targetCompany): array
    {
        return [
            'warehouse' => $targetCompany->defaultStockLocation,
            'vendor' => $targetCompany->vendorLocation,
            'customer' => $targetCompany->customerLocation ?? $targetCompany->vendorLocation,
        ];
    }

    /**
     * Process incoming stock for inter-company transfers with appropriate costing
     */
    private function processInterCompanyIncomingStock(StockMove $receiptMove, StockMove $sourceMove): void
    {
        // For inter-company transfers, we need to determine the cost
        // This could be based on the source company's cost or a transfer price
        $transferPrice = $this->calculateInterCompanyTransferPrice($sourceMove);
        
        $this->inventoryValuationService->processIncomingStock(
            $receiptMove->product,
            $receiptMove->quantity,
            $transferPrice,
            $receiptMove->move_date,
            $receiptMove
        );
    }

    /**
     * Process outgoing stock for inter-company transfers
     */
    private function processInterCompanyOutgoingStock(StockMove $deliveryMove): void
    {
        $this->inventoryValuationService->processOutgoingStock(
            $deliveryMove->product,
            $deliveryMove->quantity,
            $deliveryMove->move_date,
            $deliveryMove
        );
    }

    /**
     * Calculate the transfer price for inter-company stock transfers
     * This could be based on various methods: cost, market price, or predetermined transfer pricing
     */
    private function calculateInterCompanyTransferPrice(StockMove $sourceMove): \Brick\Money\Money
    {
        // For now, use the product's average cost or standard cost
        // In a real implementation, this could be more sophisticated
        $product = $sourceMove->product;
        
        if ($product->average_cost && !$product->average_cost->isZero()) {
            return $product->average_cost;
        }
        
        // Fallback to a default cost if no average cost is available
        $currency = $sourceMove->company->currency;
        return \Brick\Money\Money::of(0, $currency->code);
    }

    /**
     * Check if a stock move should trigger inter-company processing
     */
    public function shouldProcessInterCompany(StockMove $stockMove): ?Company
    {
        // Prevent circular processing - if this move was already created from an inter-company transfer
        if (str_starts_with($stockMove->reference ?? '', 'IC-TRANSFER-')) {
            return null;
        }

        // Check if the move involves locations from different companies
        $fromLocation = $stockMove->fromLocation;
        $toLocation = $stockMove->toLocation;

        // For outgoing moves, check if destination is linked to another company
        if ($stockMove->move_type === StockMoveType::Outgoing) {
            return $this->findLinkedCompanyForLocation($toLocation, $stockMove->company_id);
        }

        // For incoming moves, check if source is linked to another company
        if ($stockMove->move_type === StockMoveType::Incoming) {
            return $this->findLinkedCompanyForLocation($fromLocation, $stockMove->company_id);
        }

        return null;
    }

    /**
     * Find the linked company for a stock location
     */
    private function findLinkedCompanyForLocation(StockLocation $location, int $excludeCompanyId): ?Company
    {
        // This is a simplified implementation
        // In practice, you might have more sophisticated logic to determine
        // which company a location represents
        
        if ($location->company_id === $excludeCompanyId) {
            return null;
        }

        return $location->company;
    }
}
