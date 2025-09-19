<?php

namespace App\Services\Inventory;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\DataTransferObjects\Inventory\AdjustInventoryDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\ValuationMethod;
use App\Models\Company;
use App\Models\InventoryCostLayer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\StockMoveValuation;
use App\Services\Accounting\LockDateService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Valuation Service
 *
 * This service handles all inventory valuation operations including incoming and outgoing stock processing,
 * cost layer management for FIFO/LIFO methods, average cost calculations for AVCO, and automatic journal
 * entry creation following Anglo-Saxon accounting principles.
 *
 * Key Features:
 * - Multi-method valuation support (FIFO, LIFO, AVCO)
 * - Automatic journal entry generation
 * - Cost layer management for detailed traceability
 * - Lock date enforcement for data integrity
 * - Multi-currency support with proper conversion
 *
 * Accounting Integration:
 * - Incoming stock: Debit Inventory Asset, Credit Stock Input Liability
 * - Outgoing stock: Debit COGS Expense, Credit Inventory Asset
 * - Inventory adjustments: Debit/Credit Inventory and Adjustment accounts
 *
 * @package App\Services\Inventory
 * @author Laravel/Filament Inventory System
 * @version 1.0.0
 */
class InventoryValuationService
{
    /**
     * Create a new inventory valuation service instance
     *
     * @param LockDateService $lockDateService Service for enforcing accounting lock dates
     */
    public function __construct(protected LockDateService $lockDateService) {}

    /**
     * Process incoming stock and update inventory valuation
     *
     * This method handles the complete incoming stock workflow including:
     * - AVCO/FIFO/LIFO valuation calculation
     * - Journal entry creation (Inventory Dr / Stock Input Cr)
     * - Cost layer management for FIFO/LIFO
     * - Product quantity and average cost updates
     *
     * The method enforces lock date restrictions and validates that Standard costing
     * is not used (not supported in current implementation).
     *
     * @param Product $product The product receiving stock (must be storable type)
     * @param float $quantity Quantity being received (must be positive)
     * @param Money $costPerUnit Cost per unit in company base currency
     * @param Carbon $date Transaction date for valuation (must be after lock date)
     * @param \Illuminate\Database\Eloquent\Model $sourceDocument Source document (VendorBill, etc.)
     *
     * @throws Exception When Standard costing is used (not supported)
     * @throws \App\Exceptions\LockDateException When transaction date is before lock date
     *
     * @example
     * $service->processIncomingStock(
     *     $product,
     *     100.0,
     *     Money::of(1500, 'USD'),
     *     Carbon::now(),
     *     $vendorBill
     * );
     *
     * @return void
     */
    public function processIncomingStock(Product $product, float $quantity, Money $costPerUnit, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): void
    {
        $this->lockDateService->enforce(Company::findOrFail($product->company_id), Carbon::parse($date));

        if ($product->inventory_valuation_method === ValuationMethod::STANDARD) {
            throw new Exception('Standard costing is not supported in Phase 1');
        }

        // Calculate total cost for this incoming stock
        $totalCost = $costPerUnit->multipliedBy($quantity);

        // Process based on valuation method
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $this->processIncomingStockAVCO($product, $quantity, $costPerUnit);
        } else {
            $this->processIncomingStockFIFOLIFO($product, $quantity, $costPerUnit, $date, $sourceDocument);
        }

        // Create journal entry for incoming stock
        $journalEntry = $this->createIncomingStockJournalEntry($product, $totalCost, $date, $sourceDocument);

        // Create StockMoveValuation record
        $this->createIncomingStockMoveValuation($product, $quantity, $totalCost, $journalEntry, $sourceDocument);

        Log::info("Successfully processed incoming stock for product {$product->id}, Total cost: {$totalCost->getAmount()}");
    }

    /**
     * Process outgoing stock and calculate cost of goods sold (COGS)
     *
     * This method handles the complete outgoing stock workflow including:
     * - COGS calculation based on product valuation method
     * - Cost layer consumption for FIFO/LIFO products
     * - Journal entry creation (COGS Dr / Inventory Cr)
     * - Product quantity updates
     * - Stock move valuation tracking
     *
     * The method automatically determines the appropriate cost based on the product's
     * valuation method and creates the necessary accounting entries.
     *
     * @param Product $product The product being consumed (must be storable type)
     * @param float $quantity Quantity being consumed (must be positive)
     * @param Carbon $date Transaction date for COGS calculation (must be after lock date)
     * @param \Illuminate\Database\Eloquent\Model $sourceDocument Source document (CustomerInvoice, StockMove, etc.)
     *
     * @throws Exception When Standard costing is used (not supported)
     * @throws \App\Exceptions\LockDateException When transaction date is before lock date
     *
     * @example
     * $service->processOutgoingStock(
     *     $product,
     *     50.0,
     *     Carbon::now(),
     *     $customerInvoice
     * );
     *
     * @return void
     */
    public function processOutgoingStock(Product $product, float $quantity, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): void
    {
        $this->lockDateService->enforce(Company::findOrFail($product->company_id), Carbon::parse($date));

        if ($product->inventory_valuation_method === ValuationMethod::STANDARD) {
            throw new Exception('Standard costing is not supported in Phase 1');
        }

        // Calculate COGS based on valuation method
        $cogsAmount = $this->calculateCOGS($product, $quantity);

        if ($cogsAmount->isZero()) {
            Log::warning("COGS amount is zero for product {$product->id}, skipping journal entry creation");

            return;
        }

        // Create COGS journal entry
        $journalEntry = $this->createCOGSJournalEntry($product, $cogsAmount, $date, $sourceDocument);

        // Create StockMoveValuation record
        $moveType = ($sourceDocument instanceof StockMove && $sourceDocument->move_type === StockMoveType::Adjustment)
            ? StockMoveType::Adjustment
            : StockMoveType::Outgoing;
        $this->createStockMoveValuation($product, $quantity, $cogsAmount, $journalEntry, $sourceDocument, $moveType);

        // Update product quantity on hand for outgoing stock
        $product->forceFill([
            'quantity_on_hand' => max(0, $product->quantity_on_hand - $quantity),
        ])->save();

        Log::info("Successfully processed outgoing stock for product {$product->id}, COGS: {$cogsAmount->getAmount()}");
    }

    /**
     * Adjust inventory value for quantity discrepancies or write-offs
     *
     * This method handles inventory adjustments by creating appropriate journal entries
     * to reflect changes in inventory value due to:
     * - Physical count discrepancies
     * - Damaged or obsolete inventory write-offs
     * - Inventory revaluations
     *
     * The adjustment uses the product's current average cost to calculate the value impact
     * and creates journal entries following standard inventory adjustment accounting.
     *
     * @param AdjustInventoryDTO $dto Data transfer object containing adjustment details
     *
     * @throws \Exception When product doesn't have an average cost
     * @throws \App\Exceptions\LockDateException When adjustment date is before lock date
     *
     * @example
     * $dto = new AdjustInventoryDTO(
     *     product_id: 123,
     *     quantity: -10.0,  // Negative for write-offs
     *     adjustment_date: Carbon::now(),
     *     reference: 'Physical count adjustment'
     * );
     * $service->adjustInventoryValue($dto);
     *
     * @return void
     */
    public function adjustInventoryValue(AdjustInventoryDTO $dto): void
    {
        $product = Product::findOrFail($dto->product_id);
        $this->lockDateService->enforce(Company::findOrFail($product->company_id), Carbon::parse($dto->adjustment_date));

        // This is a simplified example. A real implementation would need to calculate the value of the adjustment.
        // For now, we will assume the value is the quantity * the product's average cost.
        if (! $product->average_cost) {
            throw new \Exception('Product must have an average cost for inventory adjustment');
        }
        $adjustmentValue = $product->average_cost->multipliedBy($dto->quantity);

        $journal = JournalEntry::create([
            'company_id' => $product->company_id,
            'journal_id' => $product->company->default_purchase_journal_id,
            'date' => $dto->adjustment_date,
            'state' => JournalEntryState::Posted,
            'reference' => $dto->reference ?? "Inventory Adjustment for product {$product->name}",
        ]);

        // Debit the inventory adjustment account
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $product->company->inventory_adjustment_account_id,
            'partner_id' => null,
            'label' => "Inventory Adjustment for product {$product->name}",
            'debit' => $adjustmentValue,
            'credit' => Money::of(0, $product->company->currency->code),
        ]);

        // Credit the stock valuation account
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $product->default_inventory_account_id,
            'partner_id' => null,
            'label' => "Inventory Adjustment for product {$product->name}",
            'debit' => Money::of(0, $product->company->currency->code),
            'credit' => $adjustmentValue,
        ]);
    }

    /**
     * Calculate the Cost of Goods Sold based on the product's valuation method
     */
    private function calculateCOGS(Product $product, float $quantity): Money
    {
        $company = $product->company;
        $currencyCode = $company->currency->code;

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            // For AVCO, use the product's average cost
            if (! $product->average_cost) {
                Log::warning("Product {$product->id} has no average cost set, returning zero COGS");

                return Money::of(0, $currencyCode);
            }

            return $product->average_cost->multipliedBy($quantity);
        } else {
            // For FIFO/LIFO, consume inventory cost layers
            return $this->calculateCOGSFromCostLayers($product, $quantity);
        }
    }

    /**
     * Calculate COGS by consuming inventory cost layers (FIFO/LIFO)
     */
    private function calculateCOGSFromCostLayers(Product $product, float $quantity): Money
    {
        $company = $product->company;
        $currencyCode = $company->currency->code;
        $totalCOGS = Money::of(0, $currencyCode);
        $remainingQuantity = $quantity;

        // Get cost layers ordered by date (FIFO) or reverse date (LIFO)
        $orderDirection = $product->inventory_valuation_method === ValuationMethod::FIFO ? 'asc' : 'desc';

        $costLayers = InventoryCostLayer::where('product_id', $product->id)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', $orderDirection)
            ->get();

        foreach ($costLayers as $layer) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $quantityToConsume = min($remainingQuantity, $layer->remaining_quantity);
            if (! $layer->cost_per_unit) {
                throw new \Exception('Cost layer must have a cost per unit');
            }
            $layerCOGS = $layer->cost_per_unit->multipliedBy($quantityToConsume);
            $totalCOGS = $totalCOGS->plus($layerCOGS);

            // Update the cost layer
            $layer->remaining_quantity -= $quantityToConsume;
            $layer->save();

            $remainingQuantity -= $quantityToConsume;
        }

        if ($remainingQuantity > 0) {
            Log::warning("Insufficient inventory cost layers for product {$product->id}. Missing quantity: {$remainingQuantity}");
        }

        return $totalCOGS;
    }

    /**
     * Create a journal entry for Cost of Goods Sold
     */
    private function createCOGSJournalEntry(Product $product, Money $cogsAmount, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): JournalEntry
    {
        $company = $product->company;
        $currencyCode = $company->currency->code;
        $zero = Money::of(0, $currencyCode);

        // Validate required accounts
        if (! $product->default_cogs_account_id) {
            throw new Exception("Product {$product->id} does not have a COGS account configured");
        }
        if (! $product->default_inventory_account_id) {
            throw new Exception("Product {$product->id} does not have an inventory account configured");
        }

        // Use the sales journal for COGS entries (or create a dedicated inventory journal if needed)
        $journalId = $company->default_sales_journal_id;
        if (! $journalId) {
            throw new Exception("Company {$company->id} does not have a default sales journal configured");
        }

        // Generate reference - include product ID to make it unique per product
        $sourceType = class_basename($sourceDocument);
        $sourceId = $sourceDocument->id ?? 'unknown';
        $reference = "COGS-{$sourceType}-{$sourceId}-P{$product->id}";

        // Check if a COGS journal entry already exists for this source document and product
        $existingEntry = JournalEntry::where('company_id', $company->id)
            ->where('reference', $reference)
            ->first();

        if ($existingEntry) {
            Log::info("COGS journal entry already exists for {$sourceType} {$sourceId} Product {$product->id}, skipping creation");

            return $existingEntry;
        }

        // Create journal entry DTO
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $journalId,
            currency_id: $company->currency_id,
            entry_date: $date->toDateString(),
            reference: $reference,
            description: "Cost of Goods Sold for {$product->name}",
            created_by_user_id: (int) (Auth::id() ?? 1), // Fallback to system user if no auth context
            is_posted: true, // COGS entries are posted immediately
            lines: [
                // Debit: COGS Account (expense increases)
                new CreateJournalEntryLineDTO(
                    account_id: $product->default_cogs_account_id,
                    debit: $cogsAmount,
                    credit: $zero,
                    description: "COGS for {$product->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
                // Credit: Inventory Account (asset decreases)
                new CreateJournalEntryLineDTO(
                    account_id: $product->default_inventory_account_id,
                    debit: $zero,
                    credit: $cogsAmount,
                    description: "Inventory reduction for {$product->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
            ],
            source_type: get_class($sourceDocument),
            source_id: $sourceDocument->id ?? null,
        );

        // Create the journal entry using the action
        return app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }

    /**
     * Create a StockMoveValuation record to link the stock move with its accounting impact
     *
     * @param  \Illuminate\Database\Eloquent\Model  $sourceDocument
     */
    private function createStockMoveValuation(Product $product, float $quantity, Money $cogsAmount, JournalEntry $journalEntry, $sourceDocument, ?StockMoveType $moveType = null): StockMoveValuation
    {
        // If the source document is already a StockMove, use it directly
        if ($sourceDocument instanceof StockMove) {
            $stockMove = $sourceDocument;
        } else {
            // Find the related stock move
            $query = StockMove::where('product_id', $product->id)
                ->where('source_type', get_class($sourceDocument))
                ->where('source_id', $sourceDocument->id ?? null);

            if ($moveType) {
                $query->where('move_type', $moveType);
            } else {
                $query->where('move_type', StockMoveType::Outgoing);
            }

            $stockMove = $query->first();

            if (! $stockMove) {
                $moveTypeStr = $moveType ? $moveType->value : 'outgoing';
                throw new Exception("No {$moveTypeStr} stock move found for product {$product->id} and source document");
            }
        }

        return StockMoveValuation::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'stock_move_id' => $stockMove->id,
            'quantity' => $quantity,
            'cost_impact' => $cogsAmount,
            'valuation_method' => $product->inventory_valuation_method,
            'move_type' => StockMoveType::Outgoing,
            'journal_entry_id' => $journalEntry->id,
            'source_type' => get_class($sourceDocument),
            'source_id' => $sourceDocument->id ?? null,
        ]);
    }

    /**
     * Process incoming stock for AVCO valuation method
     */
    private function processIncomingStockAVCO(Product $product, float $quantity, Money $costPerUnit): void
    {
        $company = $product->company;
        $currencyCode = $company->currency->code;

        // Calculate new average cost using weighted average
        // Get current quantity from stock quants
        $stockQuantService = app(StockQuantService::class);
        $currentQuantity = $stockQuantService->getTotalQuantity($company->id, $product->id);

        $currentValue = ($product->average_cost ?? Money::of(0, $currencyCode))->multipliedBy($currentQuantity);

        $incomingValue = $costPerUnit->multipliedBy($quantity);
        $totalQuantity = $currentQuantity + $quantity;
        $totalValue = $currentValue->plus($incomingValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::of(0, $currencyCode);

        // Update product's average cost and quantity on hand (bypass fillable)
        $product->forceFill([
            'average_cost' => $newAverageCost,
            'quantity_on_hand' => $totalQuantity,
        ])->save();

        Log::info("Updated AVCO for product {$product->id}: new average cost {$newAverageCost->getAmount()}, quantity {$totalQuantity}");
    }

    /**
     * Process incoming stock for FIFO/LIFO valuation methods
     */
    private function processIncomingStockFIFOLIFO(Product $product, float $quantity, Money $costPerUnit, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): void
    {
        // Create a new cost layer for FIFO/LIFO tracking
        InventoryCostLayer::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'purchase_date' => $date,
            'source_type' => get_class($sourceDocument),
            'source_id' => $sourceDocument->id ?? null,
        ]);

        // Update product quantity (bypass fillable)
        $product->forceFill([
            'quantity_on_hand' => $product->quantity_on_hand + $quantity,
        ])->save();

        Log::info("Created cost layer for product {$product->id}: quantity {$quantity}, unit cost {$costPerUnit->getAmount()}");
    }

    /**
     * Create a journal entry for incoming stock
     *
     * @param  \Illuminate\Database\Eloquent\Model  $sourceDocument
     */
    private function createIncomingStockJournalEntry(Product $product, Money $totalCost, Carbon $date, $sourceDocument): JournalEntry
    {
        $company = $product->company;
        $currencyCode = $company->currency->code;
        $zero = Money::of(0, $currencyCode);

        // Validate required accounts
        if (! $product->default_inventory_account_id) {
            throw new Exception("Product {$product->id} does not have an inventory account configured");
        }
        if (! $product->default_stock_input_account_id) {
            throw new Exception("Product {$product->id} does not have a stock input account configured");
        }

        // Use the purchase journal for incoming stock entries
        $journalId = $company->default_purchase_journal_id;
        if (! $journalId) {
            throw new Exception("Company {$company->id} does not have a default purchase journal configured");
        }

        // Generate reference
        $sourceType = class_basename($sourceDocument);
        $sourceId = $sourceDocument->id ?? 'unknown';
        $reference = "STOCK-IN-{$sourceType}-{$sourceId}";

        // Create journal entry DTO
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $journalId,
            currency_id: $company->currency_id,
            entry_date: $date->toDateString(),
            reference: $reference,
            description: "Stock receipt for {$product->name}",
            created_by_user_id: (int) (Auth::id() ?? 1), // Fallback to system user if no auth context
            is_posted: true, // Stock entries are posted immediately
            lines: [
                // Debit: Inventory Account (asset increases)
                new CreateJournalEntryLineDTO(
                    account_id: $product->default_inventory_account_id,
                    debit: $totalCost,
                    credit: $zero,
                    description: "Stock receipt for {$product->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
                // Credit: Stock Input Account (liability/expense decreases or payable increases)
                new CreateJournalEntryLineDTO(
                    account_id: $product->default_stock_input_account_id,
                    debit: $zero,
                    credit: $totalCost,
                    description: "Stock input for {$product->name}",
                    partner_id: null,
                    analytic_account_id: null,
                ),
            ],
            source_type: get_class($sourceDocument),
            source_id: $sourceDocument->id ?? null,
        );

        // Create the journal entry using the action
        return app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }

    /**
     * Create a StockMoveValuation record for incoming stock
     *
     * @param  \Illuminate\Database\Eloquent\Model  $sourceDocument
     */
    private function createIncomingStockMoveValuation(Product $product, float $quantity, Money $totalCost, JournalEntry $journalEntry, $sourceDocument): StockMoveValuation
    {
        // If the source document is already a StockMove, use it directly
        if ($sourceDocument instanceof StockMove) {
            $stockMove = $sourceDocument;
        } else {
            // Find the related stock move
            $stockMove = StockMove::where('product_id', $product->id)
                ->where('source_type', get_class($sourceDocument))
                ->where('source_id', $sourceDocument->id ?? null)
                ->where('move_type', StockMoveType::Incoming)
                ->first();

            if (! $stockMove) {
                throw new Exception("No incoming stock move found for product {$product->id} and source document");
            }
        }

        return StockMoveValuation::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'stock_move_id' => $stockMove->id,
            'quantity' => $quantity,
            'cost_impact' => $totalCost,
            'valuation_method' => $product->inventory_valuation_method,
            'move_type' => StockMoveType::Incoming,
            'journal_entry_id' => $journalEntry->id,
            'source_type' => get_class($sourceDocument),
            'source_id' => $sourceDocument->id ?? null,
        ]);
    }
}
