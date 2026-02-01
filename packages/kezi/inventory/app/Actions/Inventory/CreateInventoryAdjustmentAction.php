<?php

namespace Kezi\Inventory\Actions\Inventory;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Accounting\Models\JournalEntryLine;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateInventoryAdjustmentDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Kezi\Inventory\Enums\Inventory\StockMoveStatus;
use Kezi\Inventory\Enums\Inventory\StockMoveType;
use Kezi\Inventory\Enums\Inventory\StockPickingState;
use Kezi\Inventory\Enums\Inventory\StockPickingType;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveLine;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Inventory\Models\StockPicking;
use Kezi\Inventory\Services\Inventory\StockMoveService;
use Kezi\Inventory\Services\Inventory\StockQuantService;
use Kezi\Product\Models\Product;

class CreateInventoryAdjustmentAction
{
    public function __construct(
        private readonly StockQuantService $stockQuantService,
        private readonly StockMoveService $stockMoveService,
    ) {}

    /**
     * Execute inventory adjustment
     */
    public function execute(CreateInventoryAdjustmentDTO $dto): ?StockPicking
    {
        // Validate input
        $this->validateAdjustment($dto);

        return DB::transaction(function () use ($dto) {
            $company = Company::findOrFail($dto->company_id);
            $adjustmentLocation = $company->adjustmentLocation;

            if (! $adjustmentLocation) {
                throw new InvalidArgumentException('Company must have an adjustment location configured');
            }

            $picking = null;
            $hasAdjustments = false;

            foreach ($dto->lines as $line) {
                if (! $line->requiresAdjustment()) {
                    continue; // Skip lines with no change
                }

                if (! $picking) {
                    // Create picking on first adjustment line
                    $picking = $this->createAdjustmentPicking($dto, $adjustmentLocation->id);
                }

                $this->processAdjustmentLine($dto, $line, $picking, $adjustmentLocation->id);
                $hasAdjustments = true;
            }

            if ($hasAdjustments && $picking) {
                // Mark picking as done
                $picking->update([
                    'state' => StockPickingState::Done,
                    'completed_at' => now(),
                ]);
            }

            return $picking;
        });
    }

    /**
     * Validate the adjustment DTO
     */
    private function validateAdjustment(CreateInventoryAdjustmentDTO $dto): void
    {
        foreach ($dto->lines as $line) {
            if ($line->counted_quantity < 0) {
                throw new InvalidArgumentException('Counted quantity cannot be negative');
            }
        }
    }

    /**
     * Create adjustment picking
     */
    private function createAdjustmentPicking(CreateInventoryAdjustmentDTO $dto, int $adjustmentLocationId): StockPicking
    {
        return StockPicking::create([
            'company_id' => $dto->company_id,
            'type' => StockPickingType::Internal,
            'state' => StockPickingState::Draft,
            'partner_id' => null,
            'scheduled_date' => $dto->adjustment_date,
            'reference' => $dto->reference,
            'origin' => $dto->reference,
            'created_by_user_id' => $dto->created_by_user_id,
        ]);
    }

    /**
     * Process a single adjustment line
     */
    private function processAdjustmentLine(
        CreateInventoryAdjustmentDTO $dto,
        $line,
        StockPicking $picking,
        int $adjustmentLocationId,
    ): void {
        $adjustmentQty = $line->getAdjustmentQuantity();

        if ($adjustmentQty > 0) {
            // Positive adjustment: from adjustment location to stock location
            $this->createPositiveAdjustment($dto, $line, $picking, $adjustmentLocationId, $adjustmentQty);
        } else {
            // Negative adjustment: from stock location to adjustment location
            $this->createNegativeAdjustment($dto, $line, $picking, $adjustmentLocationId, abs($adjustmentQty));
        }
    }

    /**
     * Create positive adjustment (increase stock)
     */
    private function createPositiveAdjustment(
        CreateInventoryAdjustmentDTO $dto,
        $line,
        StockPicking $picking,
        int $adjustmentLocationId,
        float $quantity,
    ): void {
        $product = Product::find($line->product_id);
        $productLineDto = new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: $quantity,
            from_location_id: $adjustmentLocationId,
            to_location_id: $line->location_id,
            description: "Adjustment for {$product->name}",
            source_type: 'InventoryAdjustment',
            source_id: 1,
        );

        $moveDto = new CreateStockMoveDTO(
            company_id: $dto->company_id,
            product_lines: [$productLineDto],
            move_type: StockMoveType::Adjustment,
            status: StockMoveStatus::Draft, // Create as draft first
            move_date: $dto->adjustment_date,
            created_by_user_id: $dto->created_by_user_id,
            reference: $dto->reference,
            source_type: 'InventoryAdjustment',
            source_id: 1,
        );

        $move = $this->createStockMove($moveDto, $picking);

        // Create move line if lot is specified
        if ($line->lot_id) {
            $productLine = $move->productLines()->first();
            if ($productLine) {
                StockMoveLine::create([
                    'company_id' => $dto->company_id,
                    'stock_move_product_line_id' => $productLine->id,
                    'lot_id' => $line->lot_id,
                    'quantity' => $quantity,
                ]);
            }
        }

        // Manually update quants (observer skips adjustments)
        $this->stockQuantService->adjust(
            $dto->company_id,
            $line->product_id,
            $line->location_id,
            $quantity,
            0,
            $line->lot_id
        );

        // Create custom adjustment valuation with adjustment accounts
        $this->processPositiveAdjustmentValuation($move, $quantity);

        // Now mark as done
        $move->status = StockMoveStatus::Done;
        $move->save();
    }

    /**
     * Create negative adjustment (decrease stock)
     */
    private function createNegativeAdjustment(
        CreateInventoryAdjustmentDTO $dto,
        $line,
        StockPicking $picking,
        int $adjustmentLocationId,
        float $quantity,
    ): void {
        $product = Product::find($line->product_id);
        $productLineDto = new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: $quantity,
            from_location_id: $line->location_id,
            to_location_id: $adjustmentLocationId,
            description: "Adjustment for {$product->name}",
            source_type: 'InventoryAdjustment',
            source_id: 1,
        );

        $moveDto = new CreateStockMoveDTO(
            company_id: $dto->company_id,
            product_lines: [$productLineDto],
            move_type: StockMoveType::Adjustment,
            status: StockMoveStatus::Draft, // Create as draft first
            move_date: $dto->adjustment_date,
            created_by_user_id: $dto->created_by_user_id,
            reference: $dto->reference,
            source_type: 'InventoryAdjustment',
            source_id: 1,
        );

        $move = $this->createStockMove($moveDto, $picking);

        // Create move line if lot is specified
        if ($line->lot_id) {
            $productLine = $move->productLines()->first();
            if ($productLine) {
                StockMoveLine::create([
                    'company_id' => $dto->company_id,
                    'stock_move_product_line_id' => $productLine->id,
                    'lot_id' => $line->lot_id,
                    'quantity' => $quantity,
                ]);
            }
        }

        // Manually update quants (observer skips adjustments)
        $this->stockQuantService->adjust(
            $dto->company_id,
            $line->product_id,
            $line->location_id,
            -$quantity,
            0,
            $line->lot_id
        );

        // Create custom adjustment valuation with adjustment accounts
        $this->processNegativeAdjustmentValuation($move, $quantity);

        // Now mark as done
        $move->status = StockMoveStatus::Done;
        $move->save();
    }

    /**
     * Create stock move from DTO
     */
    private function createStockMove(CreateStockMoveDTO $dto, StockPicking $picking): StockMove
    {
        $stockMove = $this->stockMoveService->createMove($dto);

        // Associate with picking
        $stockMove->update(['picking_id' => $picking->id]);

        return $stockMove;
    }

    /**
     * Process positive adjustment valuation with adjustment account
     */
    private function processPositiveAdjustmentValuation(StockMove $move, float $quantity): void
    {
        $productLine = $move->productLines()->first();
        if (! $productLine) {
            return;
        }

        $product = $productLine->product;
        $company = $move->company;

        // Calculate cost based on product's average cost
        $costPerUnit = $product->average_cost ?? Money::of(0, $company->currency->code);
        $totalCost = $costPerUnit->multipliedBy($quantity);

        if ($totalCost->isZero()) {
            return; // No journal entry needed for zero cost
        }

        // Find a suitable journal (use sales journal as fallback)
        $journalId = $company->default_miscellaneous_journal_id ?? $company->default_sales_journal_id;

        // Create journal entry for positive adjustment
        // Debit: Inventory Account, Credit: Inventory Adjustment Account
        $journalEntry = JournalEntry::create([
            'company_id' => $company->id,
            'journal_id' => $journalId,
            'currency_id' => $company->currency_id,
            'entry_date' => $move->move_date,
            'reference' => "ADJ-{$move->reference}",
            'description' => "Inventory adjustment - {$product->name}",
            'total_debit' => $totalCost,
            'total_credit' => $totalCost,
        ]);

        // Debit inventory account
        JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $product->default_inventory_account_id,
            'debit' => $totalCost,
            'credit' => Money::of(0, $company->currency->code),
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Find an adjustment account (expense type)
        $adjustmentAccount = Account::where('company_id', $company->id)
            ->where('type', 'expense')
            ->where('name', 'LIKE', '%adjustment%')
            ->first();

        if (! $adjustmentAccount) {
            $adjustmentAccount = Account::where('company_id', $company->id)
                ->where('type', 'expense')
                ->first();
        }

        // Credit adjustment account
        JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $adjustmentAccount->id,
            'debit' => Money::of(0, $company->currency->code),
            'credit' => $totalCost,
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Create StockMoveValuation record to link the move with the journal entry
        StockMoveValuation::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'stock_move_id' => $move->id,
            'quantity' => $quantity,
            'cost_impact' => $totalCost,
            'valuation_method' => $product->inventory_valuation_method->value,
            'move_type' => $move->move_type->value,
            'journal_entry_id' => $journalEntry->id,
            'source_type' => get_class($move),
            'source_id' => $move->id,
        ]);
    }

    /**
     * Process negative adjustment valuation with adjustment account
     */
    private function processNegativeAdjustmentValuation(StockMove $move, float $quantity): void
    {
        $productLine = $move->productLines()->first();
        if (! $productLine) {
            return;
        }

        $product = $productLine->product;
        $company = $move->company;

        // Calculate cost based on product's average cost
        $costPerUnit = $product->average_cost ?? Money::of(0, $company->currency->code);
        $totalCost = $costPerUnit->multipliedBy($quantity);

        if ($totalCost->isZero()) {
            return; // No journal entry needed for zero cost
        }

        // Find a suitable journal (use sales journal as fallback)
        $journalId = $company->default_miscellaneous_journal_id ?? $company->default_sales_journal_id;

        // Create journal entry for negative adjustment
        // Debit: Inventory Adjustment Account, Credit: Inventory Account
        $journalEntry = JournalEntry::create([
            'company_id' => $company->id,
            'journal_id' => $journalId,
            'currency_id' => $company->currency_id,
            'entry_date' => $move->move_date,
            'reference' => "ADJ-{$move->reference}",
            'description' => "Inventory adjustment - {$product->name}",
            'total_debit' => $totalCost,
            'total_credit' => $totalCost,
        ]);

        // Find an adjustment account (expense type)
        $adjustmentAccount = Account::where('company_id', $company->id)
            ->where('type', 'expense')
            ->where('name', 'LIKE', '%adjustment%')
            ->first();

        if (! $adjustmentAccount) {
            $adjustmentAccount = Account::where('company_id', $company->id)
                ->where('type', 'expense')
                ->first();
        }

        // Debit adjustment account
        JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $adjustmentAccount->id,
            'debit' => $totalCost,
            'credit' => Money::of(0, $company->currency->code),
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Credit inventory account
        JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $product->default_inventory_account_id,
            'debit' => Money::of(0, $company->currency->code),
            'credit' => $totalCost,
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Create StockMoveValuation record to link the move with the journal entry
        StockMoveValuation::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'stock_move_id' => $move->id,
            'quantity' => $quantity,
            'cost_impact' => $totalCost,
            'valuation_method' => $product->inventory_valuation_method->value,
            'move_type' => $move->move_type->value,
            'journal_entry_id' => $journalEntry->id,
            'source_type' => get_class($move),
            'source_id' => $move->id,
        ]);
    }
}
