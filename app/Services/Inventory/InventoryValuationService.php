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

class InventoryValuationService
{
    public function __construct(protected LockDateService $lockDateService) {}

    public function processIncomingStock(Product $product, float $quantity, Money $costPerUnit, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): void
    {
        $this->lockDateService->enforce(Company::find($product->company_id), Carbon::parse($date));

        // Calculate total cost for this incoming stock
        $totalCost = $costPerUnit->multipliedBy($quantity);

        // Process based on valuation method
        if ($product->inventory_valuation_method === ValuationMethod::AVCO) {
            $this->processIncomingStockAVCO($product, $quantity, $costPerUnit);
        } else {
            $this->processIncomingStockFIFOLIFO($product, $quantity, $costPerUnit, $date);
        }

        // Create journal entry for incoming stock
        $journalEntry = $this->createIncomingStockJournalEntry($product, $totalCost, $date, $sourceDocument);

        // Create StockMoveValuation record
        $this->createIncomingStockMoveValuation($product, $quantity, $totalCost, $journalEntry, $sourceDocument);

        Log::info("Successfully processed incoming stock for product {$product->id}, Total cost: {$totalCost->getAmount()}");
    }

    public function processOutgoingStock(Product $product, float $quantity, Carbon $date, \Illuminate\Database\Eloquent\Model $sourceDocument): void
    {
        $this->lockDateService->enforce(Company::find($product->company_id), Carbon::parse($date));

        // Calculate COGS based on valuation method
        $cogsAmount = $this->calculateCOGS($product, $quantity);

        if ($cogsAmount->isZero()) {
            Log::warning("COGS amount is zero for product {$product->id}, skipping journal entry creation");

            return;
        }

        // Create COGS journal entry
        $journalEntry = $this->createCOGSJournalEntry($product, $cogsAmount, $date, $sourceDocument);

        // Create StockMoveValuation record
        $this->createStockMoveValuation($product, $quantity, $cogsAmount, $journalEntry, $sourceDocument);

        Log::info("Successfully processed outgoing stock for product {$product->id}, COGS: {$cogsAmount->getAmount()}");
    }

    public function adjustInventoryValue(AdjustInventoryDTO $dto): void
    {
        $product = Product::findOrFail($dto->product_id);
        $this->lockDateService->enforce(Company::find($product->company_id), Carbon::parse($dto->adjustment_date));

        // This is a simplified example. A real implementation would need to calculate the value of the adjustment.
        // For now, we will assume the value is the quantity * the product's average cost.
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
    private function createStockMoveValuation(Product $product, float $quantity, Money $cogsAmount, JournalEntry $journalEntry, $sourceDocument): StockMoveValuation
    {
        // Find the related stock move
        $stockMove = StockMove::where('product_id', $product->id)
            ->where('source_type', get_class($sourceDocument))
            ->where('source_id', $sourceDocument->id ?? null)
            ->where('move_type', StockMoveType::Outgoing)
            ->first();

        if (! $stockMove) {
            throw new Exception("No outgoing stock move found for product {$product->id} and source document");
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
        $currentQuantity = $product->quantity_on_hand;
        $currentValue = ($product->average_cost ?? Money::of(0, $currencyCode))->multipliedBy($currentQuantity);

        $incomingValue = $costPerUnit->multipliedBy($quantity);
        $totalQuantity = $currentQuantity + $quantity;
        $totalValue = $currentValue->plus($incomingValue);

        $newAverageCost = $totalQuantity > 0
            ? $totalValue->dividedBy($totalQuantity, RoundingMode::HALF_UP)
            : Money::of(0, $currencyCode);

        // Update product's average cost and quantity
        $product->update([
            'average_cost' => $newAverageCost,
            'quantity_on_hand' => $totalQuantity,
        ]);

        Log::info("Updated AVCO for product {$product->id}: new average cost {$newAverageCost->getAmount()}, quantity {$totalQuantity}");
    }

    /**
     * Process incoming stock for FIFO/LIFO valuation methods
     */
    private function processIncomingStockFIFOLIFO(Product $product, float $quantity, Money $costPerUnit, Carbon $date): void
    {
        // Create a new cost layer for FIFO/LIFO tracking
        InventoryCostLayer::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'cost_per_unit' => $costPerUnit,
            'purchase_date' => $date,
        ]);

        // Update product quantity
        $product->update([
            'quantity_on_hand' => $product->quantity_on_hand + $quantity,
        ]);

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
        // Find the related stock move
        $stockMove = StockMove::where('product_id', $product->id)
            ->where('source_type', get_class($sourceDocument))
            ->where('source_id', $sourceDocument->id ?? null)
            ->where('move_type', StockMoveType::Incoming)
            ->first();

        if (! $stockMove) {
            throw new Exception("No incoming stock move found for product {$product->id} and source document");
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
