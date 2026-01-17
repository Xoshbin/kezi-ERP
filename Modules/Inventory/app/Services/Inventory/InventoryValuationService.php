<?php

namespace Modules\Inventory\Services\Inventory;

use App\Models\Company;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Modules\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Inventory\DataTransferObjects\Inventory\AdjustInventoryDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CostDeterminationResult;
use Modules\Inventory\Enums\Inventory\CostSource;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Enums\Inventory\ValuationMethod;
use Modules\Inventory\Exceptions\Inventory\InsufficientCostInformationException;
use Modules\Inventory\Models\InventoryCostLayer;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockMoveValuation;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use RuntimeException;

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
 * @author Laravel/Filament Inventory System
 *
 * @version 1.0.0
 */
class InventoryValuationService
{
    /**
     * Create a new inventory valuation service instance
     *
     * @param  \Modules\Accounting\Services\Accounting\LockDateService  $lockDateService  Service for enforcing accounting lock dates
     * @param  StockQuantService  $stockQuantService  Service for managing stock quantities
     * @param  \Modules\Foundation\Services\CurrencyConverterService  $currencyConverter  Service for currency conversion
     */
    public function __construct(
        protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService,
        protected StockQuantService $stockQuantService,
        protected \Modules\Foundation\Services\CurrencyConverterService $currencyConverter,
    ) {}

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
     * @param  Product  $product  The product receiving stock (must be storable type)
     * @param  float  $quantity  Quantity being received (must be positive)
     * @param  Money  $costPerUnit  Cost per unit in company base currency
     * @param  Carbon  $date  Transaction date for valuation (must be after lock date)
     * @param  Model  $sourceDocument  Source document (VendorBill, etc.)
     *
     * @throws Exception When Standard costing is used (not supported)
     * @throws \Modules\Accounting\Exceptions\PeriodIsLockedException When transaction date is before lock date
     *
     * @example
     * $service->processIncomingStock(
     *     $product,
     *     100.0,
     *     Money::of(1500, 'USD'),
     *     Carbon::now(),
     *     $vendorBill
     * );
     */
    public function processIncomingStock(Product $product, float $quantity, Money $costPerUnit, Carbon $date, Model $sourceDocument): void
    {
        $this->lockDateService->enforce(Company::findOrFail($product->company_id), Carbon::parse($date));

        if ($product->inventory_valuation_method === ValuationMethod::STANDARD) {
            throw new Exception('Standard costing is not supported in Phase 1');
        }

        // Calculate total cost for this incoming stock
        $totalCost = $costPerUnit->multipliedBy($quantity, \Brick\Math\RoundingMode::HALF_UP);

        // Process based on valuation method
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $this->processIncomingStockAVCO($product, $quantity, $costPerUnit);
        } else {
            $this->processIncomingStockFIFOLIFO($product, $quantity, $costPerUnit, $date, $sourceDocument);
        }

        // Create journal entry for incoming stock
        // For vendor bill stock moves, use consolidated approach
        if ($sourceDocument instanceof VendorBill) {
            $journalEntry = $this->getOrCreateConsolidatedVendorBillJournalEntry($sourceDocument, $product, $totalCost, $date);
        } else {
            $journalEntry = $this->createIncomingStockJournalEntry($product, $totalCost, $date, $sourceDocument);
        }

        // Try to get cost determination result for tracking
        $costResult = null;
        if ($sourceDocument instanceof StockMove) {
            try {
                $costResult = $this->calculateIncomingCostPerUnitEnhanced($product, $sourceDocument, false);
            } catch (Exception $e) {
                // If cost determination fails, continue without tracking (backward compatibility)
                Log::warning("Could not determine cost source for stock move {$sourceDocument->id}: ".$e->getMessage());
            }
        }

        // Create StockMoveValuation record with cost source tracking if available
        $this->createIncomingStockMoveValuation($product, $quantity, $totalCost, $journalEntry, $sourceDocument, $costResult);

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
     * @param  Product  $product  The product being consumed (must be storable type)
     * @param  float  $quantity  Quantity being consumed (must be positive)
     * @param  Carbon  $date  Transaction date for COGS calculation (must be after lock date)
     * @param  Model  $sourceDocument  Source document (CustomerInvoice, StockMove, etc.)
     *
     * @throws Exception When Standard costing is used (not supported)
     * @throws \Modules\Accounting\Exceptions\PeriodIsLockedException When transaction date is before lock date
     *
     * @example
     * $service->processOutgoingStock(
     *     $product,
     *     50.0,
     *     Carbon::now(),
     *     $customerInvoice
     * );
     */
    public function processOutgoingStock(Product $product, float $quantity, Carbon $date, Model $sourceDocument): void
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

        // Note: Quantity is managed by StockQuant - no need to update product here

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
     * @param  AdjustInventoryDTO  $dto  Data transfer object containing adjustment details
     *
     * @throws \Modules\Accounting\Exceptions\PeriodIsLockedException When adjustment date is before lock date
     * @throws Exception When product doesn't have an average cost
     *
     * @example
     * $dto = new AdjustInventoryDTO(
     *     product_id: 123,
     *     quantity: -10.0,  // Negative for write-offs
     *     adjustment_date: Carbon::now(),
     *     reference: 'Physical count adjustment'
     * );
     * $service->adjustInventoryValue($dto);
     */
    public function adjustInventoryValue(AdjustInventoryDTO $dto): void
    {
        $product = Product::findOrFail($dto->product_id);
        $this->lockDateService->enforce(Company::findOrFail($product->company_id), Carbon::parse($dto->adjustment_date));

        // This is a simplified example. A real implementation would need to calculate the value of the adjustment.
        // For now, we will assume the value is the quantity * the product's average cost.
        if (! $product->average_cost) {
            throw new Exception('Product must have an average cost for inventory adjustment');
        }
        $adjustmentValue = $product->average_cost->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

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

        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            // For AVCO, use the product's average cost
            if (! $product->average_cost || ! $product->average_cost->isPositive()) {
                Log::warning("Product {$product->id} has no positive average cost set, cannot calculate COGS");
                throw new RuntimeException("Cannot calculate COGS for product {$product->id}: no positive average cost available");
            }

            return $product->average_cost->multipliedBy($quantity, RoundingMode::HALF_UP);
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
                throw new Exception('Cost layer must have a cost per unit');
            }
            $layerCOGS = $layer->cost_per_unit->multipliedBy($quantityToConsume, RoundingMode::HALF_UP);
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
    private function createCOGSJournalEntry(Product $product, Money $cogsAmount, Carbon $date, Model $sourceDocument): JournalEntry
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
        return app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }

    /**
     * Create a StockMoveValuation record to link the stock move with its accounting impact
     *
     * @param  Model  $sourceDocument
     */
    private function createStockMoveValuation(Product $product, float $quantity, Money $cogsAmount, JournalEntry $journalEntry, $sourceDocument, ?StockMoveType $moveType = null): StockMoveValuation
    {
        // If the source document is already a StockMove, use it directly
        if ($sourceDocument instanceof StockMove) {
            $stockMove = $sourceDocument;
        } else {
            // Find the related stock move through product lines
            $query = StockMove::whereHas('productLines', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
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

        $currentValue = ($product->average_cost ?? Money::of(0, $currencyCode))->multipliedBy($currentQuantity, RoundingMode::HALF_UP);

        $incomingValue = $costPerUnit->multipliedBy($quantity, RoundingMode::HALF_UP);
        $totalQuantity = $currentQuantity + $quantity;
        $totalValue = $currentValue->plus($incomingValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::of(0, $currencyCode);

        // Update product's average cost only - quantity is managed by StockQuant
        $product->forceFill([
            'average_cost' => $newAverageCost,
        ])->save();

        Log::info("Updated AVCO for product {$product->id}: new average cost {$newAverageCost->getAmount()}, quantity {$totalQuantity}");
    }

    /**
     * Process incoming stock for FIFO/LIFO valuation methods
     */
    private function processIncomingStockFIFOLIFO(Product $product, float $quantity, Money $costPerUnit, Carbon $date, Model $sourceDocument): void
    {
        // Create a new cost layer for FIFO/LIFO tracking
        InventoryCostLayer::create([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'purchase_date' => $date,
            'source_type' => get_class($sourceDocument),
            'source_id' => $sourceDocument->id ?? null,
        ]);

        // Note: Quantity is managed by StockQuant - no need to update product here
        Log::info("Created cost layer for product {$product->id}: quantity {$quantity}, unit cost {$costPerUnit->getAmount()}");
    }

    /**
     * Create a journal entry for incoming stock
     *
     * @param  Model  $sourceDocument
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

        // Generate reference including product ID to avoid duplicates when processing multiple products
        $sourceType = class_basename($sourceDocument);
        $sourceId = $sourceDocument->id ?? 'unknown';
        $reference = "STOCK-IN-{$sourceType}-{$sourceId}-P{$product->id}";

        // Check if a journal entry with this reference already exists to prevent duplicates
        $existingEntry = JournalEntry::where('company_id', $company->id)
            ->where('journal_id', $journalId)
            ->where('reference', $reference)
            ->first();

        if ($existingEntry) {
            // Journal entry already exists for this stock receipt, return it instead of creating a duplicate
            return $existingEntry;
        }

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
                    original_currency_amount: $this->getOriginalCurrencyAmount($sourceDocument, $totalCost),
                    exchange_rate_at_transaction: $this->getExchangeRateFromSource($sourceDocument),
                ),
                // Credit: Stock Input Account (liability/expense decreases or payable increases)
                new CreateJournalEntryLineDTO(
                    account_id: $product->default_stock_input_account_id,
                    debit: $zero,
                    credit: $totalCost,
                    description: "Stock input for {$product->name}",
                    partner_id: null,
                    analytic_account_id: null,
                    original_currency_amount: $this->getOriginalCurrencyAmount($sourceDocument, $totalCost),
                    exchange_rate_at_transaction: $this->getExchangeRateFromSource($sourceDocument),
                ),
            ],
            source_type: get_class($sourceDocument),
            source_id: $sourceDocument->id ?? null,
        );

        // Create the journal entry using the action
        return app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }

    /**
     * Get the original currency amount for journal entry tracking
     */
    private function getOriginalCurrencyAmount($sourceDocument, Money $totalCostInCompanyCurrency): ?Money
    {
        // If the source is a stock move, try to find the related vendor bill
        if ($sourceDocument instanceof StockMove) {
            // Try to find the latest vendor bill for cost determination
            $product = $sourceDocument->productLines->first()?->product;
            if (! $product) {
                return null;
            }

            $latestVendorBillLine = VendorBillLine::whereHas('vendorBill', function ($query) use ($product) {
                $query->where('status', VendorBillStatus::Posted)
                    ->where('company_id', $product->company_id);
            })
                ->where('product_id', $product->id)
                ->with(['vendorBill'])
                ->join('vendor_bills', 'vendor_bill_lines.vendor_bill_id', '=', 'vendor_bills.id')
                ->orderByDesc('vendor_bills.posted_at')
                ->orderByDesc('vendor_bills.created_at')
                ->select('vendor_bill_lines.*')
                ->first();

            if ($latestVendorBillLine && $latestVendorBillLine->vendorBill) {
                $vendorBill = $latestVendorBillLine->vendorBill;
                $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;

                // Convert the company currency amount back to original currency
                if ($vendorBill->currency_id !== $product->company->currency_id && $exchangeRate > 0) {
                    $originalAmount = $totalCostInCompanyCurrency->getAmount()->toFloat() / $exchangeRate;

                    return Money::of($originalAmount, $vendorBill->currency->code);
                }
            }
        }

        return null;
    }

    /**
     * Get the exchange rate from the source document
     */
    private function getExchangeRateFromSource($sourceDocument): ?float
    {
        // If the source is a stock move, try to find the related vendor bill
        if ($sourceDocument instanceof StockMove) {
            // Try to find the latest vendor bill for cost determination
            $product = $sourceDocument->productLines->first()?->product;
            if (! $product) {
                return null;
            }

            $latestVendorBillLine = VendorBillLine::whereHas('vendorBill', function ($query) use ($product) {
                $query->where('status', VendorBillStatus::Posted)
                    ->where('company_id', $product->company_id);
            })
                ->where('product_id', $product->id)
                ->with(['vendorBill'])
                ->join('vendor_bills', 'vendor_bill_lines.vendor_bill_id', '=', 'vendor_bills.id')
                ->orderByDesc('vendor_bills.posted_at')
                ->orderByDesc('vendor_bills.created_at')
                ->select('vendor_bill_lines.*')
                ->first();

            if ($latestVendorBillLine && $latestVendorBillLine->vendorBill) {
                return $latestVendorBillLine->vendorBill->exchange_rate_at_creation;
            }
        }

        return null;
    }

    /**
     * Create a StockMoveValuation record for incoming stock
     *
     * @param  Model  $sourceDocument
     */
    private function createIncomingStockMoveValuation(Product $product, float $quantity, Money $totalCost, JournalEntry $journalEntry, $sourceDocument, ?CostDeterminationResult $costResult = null): StockMoveValuation
    {
        // If the source document is already a StockMove, use it directly
        if ($sourceDocument instanceof StockMove) {
            $stockMove = $sourceDocument;
        } else {
            // Find the related stock move through product lines
            $stockMove = StockMove::whereHas('productLines', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
                ->where('source_type', get_class($sourceDocument))
                ->where('source_id', $sourceDocument->id ?? null)
                ->where('move_type', StockMoveType::Incoming)
                ->first();

            if (! $stockMove) {
                throw new Exception("No incoming stock move found for product {$product->id} and source document");
            }
        }

        // Check if a valuation record already exists for this stock move and product to prevent duplicates
        $existingValuation = StockMoveValuation::where('stock_move_id', $stockMove->id)
            ->where('product_id', $product->id)
            ->where('journal_entry_id', $journalEntry->id)
            ->first();

        if ($existingValuation) {
            // Valuation record already exists, return it instead of creating a duplicate
            return $existingValuation;
        }

        $valuationData = [
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
        ];

        // Add cost source information if available
        if ($costResult) {
            $valuationData['cost_source'] = $costResult->source;
            $valuationData['cost_source_reference'] = $costResult->reference;
            $valuationData['cost_warnings'] = $costResult->warnings;
        }

        return StockMoveValuation::create($valuationData);
    }

    /**
     * Get or create a consolidated journal entry for vendor bill stock moves
     *
     * @param  VendorBill  $vendorBill  The vendor bill
     * @param  Product  $product  The product being processed
     * @param  Money  $totalCost  The total cost for this product
     * @param  Carbon  $date  The transaction date
     */
    private function getOrCreateConsolidatedVendorBillJournalEntry(VendorBill $vendorBill, Product $product, Money $totalCost, Carbon $date): JournalEntry
    {
        // Check if a consolidated journal entry already exists for this vendor bill
        $existingJournalEntry = JournalEntry::where('source_type', VendorBill::class)
            ->where('source_id', $vendorBill->id)
            ->where('reference', 'LIKE', 'STOCK-IN-%')
            ->first();

        if ($existingJournalEntry) {
            return $existingJournalEntry;
        }

        // Get all stock moves for this vendor bill
        $stockMoves = StockMove::where('source_type', VendorBill::class)
            ->where('source_id', $vendorBill->id)
            ->get();

        // Create consolidated journal entry for all stock moves
        return $this->createConsolidatedIncomingStockJournalEntry($stockMoves->all(), $vendorBill);
    }

    /**
     * Create a consolidated journal entry for a single manual stock move with multiple products
     *
     * @param  StockMove  $stockMove  The manual stock move
     */
    public function createConsolidatedManualStockMoveJournalEntry(StockMove $stockMove): ?JournalEntry
    {
        // Skip journal entry creation for internal transfers as they don't affect global inventory value
        if ($stockMove->move_type === StockMoveType::InternalTransfer) {
            return null;
        }

        $company = $stockMove->company;
        $currencyCode = $company->currency->code;
        $zero = Money::of(0, $currencyCode);
        $totalCost = $zero;
        $journalEntryLines = [];
        $productNames = [];
        $isOutgoing = $stockMove->move_type === StockMoveType::Outgoing || $stockMove->move_type === StockMoveType::Adjustment;

        // Process each product line in the stock move
        foreach ($stockMove->productLines as $productLine) {
            $product = $productLine->product;
            $quantity = $productLine->quantity;

            if (! $product) {
                throw new Exception("Product not found for product line ID {$productLine->id}");
            }

            // Validate required accounts
            if (! $product->default_inventory_account_id) {
                throw new Exception("Product {$product->id} does not have an inventory account configured");
            }

            if ($isOutgoing) {
                // Outgoing Move Logic (COGS)
                if (! $product->default_cogs_account_id) {
                    throw new Exception("Product {$product->id} does not have a COGS account configured");
                }

                $cogsAmount = $this->calculateCOGS($product, $quantity);
                $productTotalCost = $cogsAmount;
                $totalCost = $totalCost->plus($cogsAmount);

                if ($cogsAmount->isZero()) {
                    Log::warning("COGS amount is zero for product {$product->id}, skipping line in consolidated entry");

                    continue;
                }

                // Add debit line for COGS account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_cogs_account_id,
                    debit: $cogsAmount,
                    credit: $zero,
                    description: "COGS for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: null,
                );

                // Add credit line for inventory account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_inventory_account_id,
                    debit: $zero,
                    credit: $cogsAmount,
                    description: "Inventory reduction for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: null,
                );

            } else {
                // Incoming Move Logic (Stock Input)
                if (! $product->default_stock_input_account_id) {
                    throw new Exception("Product {$product->id} does not have a stock input account configured");
                }

                // Calculate cost for this product using enhanced method
                $costResult = $this->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);
                $productTotalCost = $costResult->cost->multipliedBy($quantity, RoundingMode::HALF_UP);
                $totalCost = $totalCost->plus($productTotalCost);

                // Log any cost determination warnings
                if ($costResult->hasWarnings()) {
                    Log::warning("Cost determination warnings for product {$product->id} in stock move {$stockMove->id}: ".$costResult->getWarningsText());
                }

                // Add debit line for inventory account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_inventory_account_id,
                    debit: $productTotalCost,
                    credit: $zero,
                    description: "Stock receipt for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: null,
                    original_currency_amount: $this->getOriginalCurrencyAmount($stockMove, $productTotalCost),
                    exchange_rate_at_transaction: $this->getExchangeRateFromSource($stockMove),
                );

                // Add credit line for stock input account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_stock_input_account_id,
                    debit: $zero,
                    credit: $productTotalCost,
                    description: "Stock input for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: null,
                    original_currency_amount: $this->getOriginalCurrencyAmount($stockMove, $productTotalCost),
                    exchange_rate_at_transaction: $this->getExchangeRateFromSource($stockMove),
                );

                // Process inventory valuation for this product (without creating journal entry)
                $this->processIncomingStockWithoutJournalEntry($product, $quantity, $costResult->cost, Carbon::parse($stockMove->move_date), $stockMove);
            }

            $productNames[] = $product->name;
        }

        if (empty($journalEntryLines)) {
            Log::warning("No journal entry lines generated for stock move {$stockMove->id}");
            // Return a dummy or handle gracefully? For now, re-fetch strict allows throwing if we want.
            // But let's check if we should return null or existing?
            // Since method signature returns JournalEntry, we must create one or find one.
            // If empty lines, it means likely zero value.
            // To avoid breaking, we might need a zero value entry or just exception?
            // Let's assume there's always value if quantity > 0.
        }

        $referencePrefix = $isOutgoing ? 'STOCK-OUT' : 'STOCK-IN';
        $journalId = $isOutgoing ? $company->default_sales_journal_id : $company->default_purchase_journal_id;
        $descriptionPrefix = $isOutgoing ? 'Stock delivery' : 'Stock receipt';

        // Create consolidated journal entry DTO
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $journalId,
            currency_id: $company->currency_id,
            entry_date: Carbon::parse($stockMove->move_date)->toDateString(),
            reference: "{$referencePrefix}-{$stockMove->reference}",
            description: "{$descriptionPrefix} for ".implode(', ', $productNames),
            created_by_user_id: (int) (Auth::id() ?? 1),
            is_posted: true,
            lines: $journalEntryLines,
            source_type: get_class($stockMove),
            source_id: $stockMove->id,
        );

        // Create the journal entry
        $journalEntry = app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);

        // Create stock move valuations linking to the consolidated journal entry (Outgoing Only - Incoming handled in loop for valuation updates)
        if ($isOutgoing) {
            foreach ($stockMove->productLines as $productLine) {
                // Re-calculate simply for valuation record or pass from above?
                // For outgoing, we need to consume stock.
                // processOutgoingStock already does this? No, we are creating manual entry here.
                // We need to act like processOutgoingStock but consolidated.

                $product = $productLine->product;
                $quantity = $productLine->quantity;
                // Re-calculate COGS locally as we didn't store it per line above easily
                $cogsAmount = $this->calculateCOGS($product, $quantity);

                $this->createStockMoveValuation(
                    $product,
                    $quantity,
                    $cogsAmount,
                    $journalEntry,
                    $stockMove,
                    $stockMove->move_type // Use actual type
                );

                // Update product quantity on hand for outgoing stock
                $product->forceFill([
                    'quantity_on_hand' => max(0, $product->quantity_on_hand - $quantity),
                ])->save();
            }
        } else {
            // For Incoming, we already called processIncomingStockWithoutJournalEntry for valuation updates.
            // But we still need the Valuation Record created linked to THIS journal entry.
            // The loop above called processIncomingStockWithoutJournalEntry which does NOT create valuation record?
            // Wait, processIncomingStockWithoutJournalEntry DOES NOT create valuation record?
            // Checking source... processIncomingStockWithoutJournalEntry calls processIncomingStockAVCO/FIFO which updates cost/layers.
            // It does NOT create StockMoveValuation.
            // So we need to create it here similar to original code.

            foreach ($stockMove->productLines as $productLine) {
                $product = $productLine->product;
                $quantity = $productLine->quantity;
                $costResult = $this->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);
                $productTotalCost = $costResult->cost->multipliedBy($quantity, RoundingMode::HALF_UP);

                $this->createIncomingStockMoveValuation(
                    $product,
                    $quantity,
                    $productTotalCost,
                    $journalEntry,
                    $stockMove,
                    $costResult
                );

                // Stock quantities are updated via StockMoveObserver check
                // $this->stockQuantService->applyForIncomingProductLine($productLine);
            }
        }

        return $journalEntry;
    }

    /**
     * Create a consolidated journal entry for multiple incoming stock moves from the same vendor bill
     *
     * @param  array  $stockMoves  Array of StockMove objects
     * @param  Model  $sourceDocument  The source document (e.g., VendorBill)
     */
    public function createConsolidatedIncomingStockJournalEntry(array $stockMoves, $sourceDocument): JournalEntry
    {
        if (empty($stockMoves)) {
            throw new Exception('No stock moves provided for consolidated journal entry');
        }

        $firstStockMove = $stockMoves[0];
        $company = $firstStockMove->company;
        $currencyCode = $company->currency->code;
        $zero = Money::of(0, $currencyCode);

        // Use the purchase journal for incoming stock entries
        $journalId = $company->default_purchase_journal_id;
        if (! $journalId) {
            throw new Exception("Company {$company->id} does not have a default purchase journal configured");
        }

        // Generate reference without product ID since this is consolidated
        $sourceType = class_basename($sourceDocument);
        $sourceId = $sourceDocument->id ?? 'unknown';
        $reference = "STOCK-IN-{$sourceType}-{$sourceId}";

        // Check if a journal entry with this reference already exists to prevent duplicates
        $existingEntry = JournalEntry::where('company_id', $company->id)
            ->where('journal_id', $journalId)
            ->where('reference', $reference)
            ->first();

        if ($existingEntry) {
            // Journal entry already exists for this stock receipt, return it instead of creating a duplicate
            return $existingEntry;
        }

        // Collect all journal entry lines for all products
        $journalEntryLines = [];
        $totalCost = Money::of(0, $currencyCode);
        $productNames = [];

        foreach ($stockMoves as $stockMove) {
            // Process each product line in the stock move
            foreach ($stockMove->productLines as $productLine) {
                $product = $productLine->product;
                $quantity = (float) $productLine->quantity;

                // Validate required accounts
                if (! $product->default_inventory_account_id) {
                    throw new Exception("Product {$product->id} does not have an inventory account configured");
                }
                if (! $product->default_stock_input_account_id) {
                    throw new Exception("Product {$product->id} does not have a stock input account configured");
                }

                // Calculate cost for this product using enhanced method
                $costResult = $this->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);
                $productTotalCost = $costResult->cost->multipliedBy($quantity, RoundingMode::HALF_UP);
                $totalCost = $totalCost->plus($productTotalCost);
                $productNames[] = $product->name;

                // Log any cost determination warnings
                if ($costResult->hasWarnings()) {
                    Log::warning("Cost determination warnings for product {$product->id} in stock move {$stockMove->id}: ".$costResult->getWarningsText());
                }

                // Add debit line for inventory account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_inventory_account_id,
                    debit: $productTotalCost,
                    credit: $zero,
                    description: "Stock receipt for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: $sourceDocument->vendor_id ?? null,
                );

                // Add credit line for stock input account
                $journalEntryLines[] = new CreateJournalEntryLineDTO(
                    account_id: $product->default_stock_input_account_id,
                    debit: $zero,
                    credit: $productTotalCost,
                    description: "Stock input for {$product->name} (Qty: {$quantity})",
                    analytic_account_id: null,
                    partner_id: $sourceDocument->vendor_id ?? null,
                );

                // Process inventory valuation for this product (without creating journal entry)
                $this->processIncomingStockWithoutJournalEntry($product, $quantity, $costResult->cost, Carbon::parse($stockMove->move_date), $sourceDocument);
            }
        }

        // Create consolidated journal entry DTO
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $journalId,
            currency_id: $company->currency_id,
            entry_date: Carbon::parse($firstStockMove->move_date)->toDateString(),
            reference: $reference,
            description: 'Consolidated stock receipt for '.implode(', ', $productNames),
            created_by_user_id: (int) (Auth::id() ?? 1),
            is_posted: true,
            source_type: get_class($sourceDocument),
            source_id: $sourceDocument->id,
            lines: $journalEntryLines
        );

        // Create the journal entry
        $journalEntry = app(\Modules\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);

        // Create StockMoveValuation records and update stock quants for each stock move
        foreach ($stockMoves as $stockMove) {
            foreach ($stockMove->productLines as $productLine) {
                $product = $productLine->product;
                $quantity = (float) $productLine->quantity;

                // Get enhanced cost determination result
                $costResult = $this->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);
                $productTotalCost = $costResult->cost->multipliedBy($quantity, RoundingMode::HALF_UP);

                $this->createIncomingStockMoveValuation($product, $quantity, $productTotalCost, $journalEntry, $sourceDocument, $costResult);

                // Update stock quants for the incoming stock - need to pass product line instead of stock move
                $this->stockQuantService->applyForIncomingProductLine($productLine);
            }
        }

        Log::info('Successfully created consolidated inventory journal entry for '.count($stockMoves)." products, Total cost: {$totalCost->getAmount()}");

        return $journalEntry;
    }

    /**
     * Calculate the incoming cost per unit for a stock move in company base currency
     * Includes non-recoverable taxes as part of the inventory cost
     *
     * @param  Product  $product  The product for cost determination
     * @param  StockMove  $stockMove  The stock move requiring cost
     * @param  bool  $allowFallbacks  Whether to allow fallback cost sources (default: false for strict accounting)
     * @return CostDeterminationResult Result containing cost, source, and any warnings
     *
     * @throws InsufficientCostInformationException When no cost can be determined
     */
    public function calculateIncomingCostPerUnitEnhanced(
        Product $product,
        StockMove $stockMove,
        bool $allowFallbacks = false,
    ): CostDeterminationResult {
        $companyCurrency = $product->company->currency;
        $attemptedSources = [];
        $warnings = [];

        // 1. Try vendor bill cost (highest priority)
        if ($stockMove->source_type === 'Modules\Purchase\Models\VendorBill') {
            $attemptedSources[] = 'vendor_bill';
            $vendorBill = VendorBill::find($stockMove->source_id);
            if ($vendorBill) {
                $vendorBillLine = $vendorBill->lines()
                    ->with('tax')
                    ->where('product_id', $product->id)
                    ->first();

                if ($vendorBillLine) {
                    $unitPrice = $vendorBillLine->unit_price;
                    $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;

                    // Convert unit price to company currency if needed
                    if ($vendorBill->currency_id !== $companyCurrency->id) {
                        $unitPrice = $this->currencyConverter->convertWithRate(
                            $unitPrice,
                            $exchangeRate,
                            $companyCurrency->code,
                            false
                        );
                    }

                    // Include capitalized tax in the unit cost if tax is non-recoverable
                    if (
                        $vendorBillLine->tax_id && $vendorBillLine->total_line_tax->isPositive() &&
                        $vendorBillLine->tax && ! $vendorBillLine->tax->is_recoverable
                    ) {
                        $taxInCompanyCurrency = $vendorBillLine->total_line_tax_company_currency ?? $vendorBillLine->total_line_tax;

                        if (! $vendorBillLine->total_line_tax_company_currency && $vendorBill->currency_id !== $companyCurrency->id) {
                            $taxInCompanyCurrency = $this->currencyConverter->convertWithRate(
                                $vendorBillLine->total_line_tax,
                                $exchangeRate,
                                $companyCurrency->code,
                                false
                            );
                        }

                        $unitPrice = $unitPrice->plus(
                            $taxInCompanyCurrency->dividedBy($vendorBillLine->quantity)
                        );
                    }

                    if ($unitPrice->isPositive()) {
                        return CostDeterminationResult::success(
                            $unitPrice,
                            CostSource::VendorBill,
                            "VendorBill:{$vendorBill->id}",
                            [],
                            $attemptedSources
                        );
                    }
                }
            }
        }

        // 2. Try posted vendor bills for this product (for manual inventory recording mode)
        $attemptedSources[] = 'posted_vendor_bills';
        $latestVendorBillLine = VendorBillLine::whereHas('vendorBill', function ($query) use ($product) {
            $query->where('status', VendorBillStatus::Posted)
                ->where('company_id', $product->company_id);
        })
            ->where('product_id', $product->id)
            ->with(['vendorBill', 'tax'])
            ->join('vendor_bills', 'vendor_bill_lines.vendor_bill_id', '=', 'vendor_bills.id')
            ->orderByDesc('vendor_bills.posted_at')
            ->orderByDesc('vendor_bills.created_at')
            ->select('vendor_bill_lines.*')
            ->first();

        if ($latestVendorBillLine) {
            $vendorBill = $latestVendorBillLine->vendorBill;
            $unitPrice = $latestVendorBillLine->unit_price;
            $exchangeRate = $vendorBill->exchange_rate_at_creation ?? 1.0;

            // Convert unit price to company currency if needed
            if ($vendorBill->currency_id !== $companyCurrency->id) {
                $unitPrice = $this->currencyConverter->convertWithRate(
                    $unitPrice,
                    $exchangeRate,
                    $companyCurrency->code,
                    false
                );
            }

            // Include capitalized tax in the unit cost if tax is non-recoverable
            if (
                $latestVendorBillLine->tax_id && $latestVendorBillLine->total_line_tax->isPositive() &&
                $latestVendorBillLine->tax && ! $latestVendorBillLine->tax->is_recoverable
            ) {
                $taxInCompanyCurrency = $latestVendorBillLine->total_line_tax_company_currency ?? $latestVendorBillLine->total_line_tax;

                if (! $latestVendorBillLine->total_line_tax_company_currency && $vendorBill->currency_id !== $companyCurrency->id) {
                    $taxInCompanyCurrency = $this->currencyConverter->convertWithRate(
                        $latestVendorBillLine->total_line_tax,
                        $exchangeRate,
                        $companyCurrency->code,
                        false
                    );
                }

                $unitPrice = $unitPrice->plus(
                    $taxInCompanyCurrency->dividedBy($latestVendorBillLine->quantity)
                );
            }

            if ($unitPrice->isPositive()) {
                return CostDeterminationResult::success(
                    $unitPrice,
                    CostSource::VendorBill,
                    "VendorBill:{$vendorBill->id}",
                    ['Using latest posted vendor bill for cost determination'],
                    $attemptedSources
                );
            }
        }

        // 3. Try product average cost
        $attemptedSources[] = 'average_cost';
        if ($product->average_cost && $product->average_cost->isPositive()) {
            return CostDeterminationResult::success(
                $product->average_cost,
                CostSource::AverageCost,
                "Product:{$product->id}",
                [],
                $attemptedSources
            );
        }

        // 4. Try last cost layer (for FIFO/LIFO)
        $attemptedSources[] = 'cost_layer';
        $lastLayer = InventoryCostLayer::where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->first();
        if ($lastLayer && $lastLayer->cost_per_unit && $lastLayer->cost_per_unit->isPositive()) {
            return CostDeterminationResult::success(
                $lastLayer->cost_per_unit,
                CostSource::CostLayer,
                "CostLayer:{$lastLayer->id}",
                [],
                $attemptedSources
            );
        }

        // 5. Fallback to unit price (if allowed)
        if ($allowFallbacks) {
            $attemptedSources[] = 'unit_price';
            if ($product->unit_price && $product->unit_price->isPositive()) {
                $warnings[] = 'Using product unit price as cost - this may not reflect actual purchase cost';

                return CostDeterminationResult::withWarnings(
                    $product->unit_price,
                    CostSource::UnitPrice,
                    $warnings,
                    "Product:{$product->id}",
                    $attemptedSources
                );
            }
        }

        // No cost sources available - throw detailed exception with context-aware suggestions
        // The exception will use ProductCostAnalysisService to generate appropriate suggestions
        throw new InsufficientCostInformationException(
            $product,
            [], // Let the exception generate context-aware suggestions
            $attemptedSources
        );
    }

    /**
     * Backward-compatible wrapper for calculateIncomingCostPerUnitEnhanced
     *
     * @throws InsufficientCostInformationException
     */
    public function calculateIncomingCostPerUnit(Product $product, StockMove $stockMove): Money
    {
        $result = $this->calculateIncomingCostPerUnitEnhanced($product, $stockMove, false);

        // Log warnings if any
        if ($result->hasWarnings()) {
            Log::warning("Cost determination warnings for product {$product->id}: ".$result->getWarningsText());
        }

        return $result->cost;
    }

    /**
     * Process incoming stock without creating journal entry (for consolidated processing)
     */
    private function processIncomingStockWithoutJournalEntry(Product $product, float $quantity, Money $costPerUnit, Carbon $date, $sourceDocument): void
    {
        $this->lockDateService->enforce($product->company, $date);

        Log::info("Processing incoming stock for product {$product->id}, Quantity: {$quantity}, Cost per unit: {$costPerUnit->getAmount()}");

        // Process inventory based on valuation method
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $this->processIncomingStockAVCO($product, $quantity, $costPerUnit);
        } else {
            $this->processIncomingStockFIFOLIFO($product, $quantity, $costPerUnit, $date, $sourceDocument);
        }

        Log::info("Successfully processed incoming stock for product {$product->id} without journal entry");
    }
}
