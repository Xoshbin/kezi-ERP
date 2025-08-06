<?php

namespace App\Services\Inventory;

use App\DataTransferObjects\Inventory\AdjustInventoryDTO;
use App\Enums\Inventory\ValuationMethod;
use App\Models\Product;
use App\Enums\Accounting\JournalEntryState;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\Accounting\AccountingValidationService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InventoryValuationService
{
    public function __construct(private readonly AccountingValidationService $accountingValidationService)
    {
    }

    public function processIncomingStock(Product $product, float $quantity, Money $costPerUnit, Carbon $date, $sourceDocument): void
    {
        $this->accountingValidationService->ensureDateIsNotLocked($product->company_id, $date);
        // Logic will depend on the product's valuation method
        if ($product->valuation_method === ValuationMethod::AVCO) {
            // Recalculate average cost
            Log::info("Processing incoming stock for AVCO product {$product->id}");
        } else {
            // Create InventoryCostLayer for FIFO/LIFO
            Log::info("Processing incoming stock for FIFO/LIFO product {$product->id}");
        }

        // Generate JournalEntry (to be implemented)
        Log::info("Journal Entry creation for incoming stock of product {$product->id}");

        // Create StockMoveValuation (to be implemented)
        Log::info("StockMoveValuation creation for incoming stock of product {$product->id}");
    }

    public function processOutgoingStock(Product $product, float $quantity, Carbon $date, $sourceDocument): void
    {
        $this->accountingValidationService->ensureDateIsNotLocked($product->company_id, $date);
        // Logic will depend on the product's valuation method
        if ($product->valuation_method === ValuationMethod::AVCO) {
            // Calculate COGS using average_cost
            Log::info("Processing outgoing stock for AVCO product {$product->id}");
        } else {
            // Consume InventoryCostLayers for FIFO/LIFO
            Log::info("Processing outgoing stock for FIFO/LIFO product {$product->id}");
        }

        // Generate JournalEntry (to be implemented)
        Log::info("Journal Entry creation for outgoing stock of product {$product->id}");

        // Create StockMoveValuation (to be implemented)
        Log::info("StockMoveValuation creation for outgoing stock of product {$product->id}");
    }

    public function adjustInventoryValue(AdjustInventoryDTO $dto): void
    {
        $product = Product::findOrFail($dto->product_id);
        $this->accountingValidationService->ensureDateIsNotLocked($product->company_id, $dto->adjustment_date);
        // This is a simplified example. A real implementation would need to calculate the value of the adjustment.
        // For now, we will assume the value is the quantity * the product's average cost.
        $adjustmentValue = $product->average_cost->multipliedBy($dto->quantity);

        $journal = JournalEntry::create([
            'company_id' => $product->company_id,
            'journal_id' => $product->company->default_inventory_journal_id,
            'date' => $dto->adjustment_date,
            'state' => JournalEntryState::Posted,
            'reference' => $dto->reference ?? "Inventory Adjustment for product {$product->name}",
        ]);

        // Debit the inventory adjustment account
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $product->company->inventoryAdjustmentAccount->id,
            'partner_id' => null,
            'label' => "Inventory Adjustment for product {$product->name}",
            'debit' => $adjustmentValue,
            'credit' => Money::of(0, $product->company->currency->code),
        ]);

        // Credit the stock valuation account
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $product->stock_valuation_account_id,
            'partner_id' => null,
            'label' => "Inventory Adjustment for product {$product->name}",
            'debit' => Money::of(0, $product->company->currency->code),
            'credit' => $adjustmentValue,
        ]);
    }
}
