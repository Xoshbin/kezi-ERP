<?php

namespace App\Actions\Inventory;

use App\DataTransferObjects\Inventory\CreateInventoryAdjustmentDTO;
use App\DataTransferObjects\Inventory\CreateStockMoveDTO;
use App\Enums\Inventory\StockMoveStatus;
use App\Enums\Inventory\StockMoveType;
use App\Enums\Inventory\StockPickingState;
use App\Enums\Inventory\StockPickingType;
use App\Models\Company;
use App\Models\StockMove;
use App\Models\StockMoveLine;
use App\Models\StockPicking;
use App\Services\Inventory\InventoryValuationService;
use App\Services\Inventory\StockQuantService;
use App\Services\Inventory\StockMoveService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateInventoryAdjustmentAction
{
    public function __construct(
        private readonly InventoryValuationService $valuationService,
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

            if (!$adjustmentLocation) {
                throw new InvalidArgumentException('Company must have an adjustment location configured');
            }

            $picking = null;
            $hasAdjustments = false;

            foreach ($dto->lines as $line) {
                if (!$line->requiresAdjustment()) {
                    continue; // Skip lines with no change
                }

                if (!$picking) {
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
        int $adjustmentLocationId
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
        float $quantity
    ): void {
        $product = \App\Models\Product::find($line->product_id);
        $productLineDto = new \App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO(
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
            status: StockMoveStatus::Done,
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

        // Update quants
        $this->stockQuantService->adjust(
            $dto->company_id,
            $line->product_id,
            $line->location_id,
            $quantity,
            0,
            $line->lot_id
        );

        // Process valuation
        $this->processAdjustmentValuation($move, $quantity);
    }

    /**
     * Create negative adjustment (decrease stock)
     */
    private function createNegativeAdjustment(
        CreateInventoryAdjustmentDTO $dto,
        $line,
        StockPicking $picking,
        int $adjustmentLocationId,
        float $quantity
    ): void {
        $product = \App\Models\Product::find($line->product_id);
        $productLineDto = new \App\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO(
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
            status: StockMoveStatus::Done,
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

        // Update quants
        $this->stockQuantService->adjust(
            $dto->company_id,
            $line->product_id,
            $line->location_id,
            -$quantity,
            0,
            $line->lot_id
        );

        // Process valuation
        $this->processAdjustmentValuation($move, $quantity);
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
     * Process valuation for adjustment
     */
    private function processAdjustmentValuation(StockMove $move, float $quantity): void
    {
        if ($move->move_type === StockMoveType::Adjustment) {
            // Get the first product line to determine adjustment direction
            $productLine = $move->productLines()->first();
            if (!$productLine) {
                return;
            }

            // Use the adjustment as source document for valuation
            if ($productLine->from_location_id === $move->company->adjustmentLocation->id) {
                // Positive adjustment - create adjustment-specific journal entry
                $this->processPositiveAdjustmentValuation($move, $quantity);
            } else {
                // Negative adjustment - create adjustment-specific journal entry
                $this->processNegativeAdjustmentValuation($move, $quantity);
            }
        }
    }

    /**
     * Process positive adjustment valuation with adjustment account
     */
    private function processPositiveAdjustmentValuation(StockMove $move, float $quantity): void
    {
        $productLine = $move->productLines()->first();
        if (!$productLine) {
            return;
        }

        $product = $productLine->product;
        $company = $move->company;

        // Calculate cost based on product's average cost
        $costPerUnit = $product->average_cost ?? \Brick\Money\Money::of(0, $company->currency->code);
        $totalCost = $costPerUnit->multipliedBy($quantity);

        if ($totalCost->isZero()) {
            return; // No journal entry needed for zero cost
        }

        // Find a suitable journal (use sales journal as fallback)
        $journalId = $company->default_miscellaneous_journal_id ?? $company->default_sales_journal_id;

        // Create journal entry for positive adjustment
        // Debit: Inventory Account, Credit: Inventory Adjustment Account
        $journalEntry = \App\Models\JournalEntry::create([
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
        \App\Models\JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $product->default_inventory_account_id,
            'debit' => $totalCost,
            'credit' => \Brick\Money\Money::of(0, $company->currency->code),
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Find an adjustment account (expense type)
        $adjustmentAccount = \App\Models\Account::where('company_id', $company->id)
            ->where('type', 'expense')
            ->where('name', 'LIKE', '%adjustment%')
            ->first();

        if (!$adjustmentAccount) {
            $adjustmentAccount = \App\Models\Account::where('company_id', $company->id)
                ->where('type', 'expense')
                ->first();
        }

        // Credit adjustment account
        \App\Models\JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $adjustmentAccount->id,
            'debit' => \Brick\Money\Money::of(0, $company->currency->code),
            'credit' => $totalCost,
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Create StockMoveValuation record to link the move with the journal entry
        \App\Models\StockMoveValuation::create([
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
        if (!$productLine) {
            return;
        }

        $product = $productLine->product;
        $company = $move->company;

        // Calculate cost based on product's average cost
        $costPerUnit = $product->average_cost ?? \Brick\Money\Money::of(0, $company->currency->code);
        $totalCost = $costPerUnit->multipliedBy($quantity);

        if ($totalCost->isZero()) {
            return; // No journal entry needed for zero cost
        }

        // Find a suitable journal (use sales journal as fallback)
        $journalId = $company->default_miscellaneous_journal_id ?? $company->default_sales_journal_id;

        // Create journal entry for negative adjustment
        // Debit: Inventory Adjustment Account, Credit: Inventory Account
        $journalEntry = \App\Models\JournalEntry::create([
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
        $adjustmentAccount = \App\Models\Account::where('company_id', $company->id)
            ->where('type', 'expense')
            ->where('name', 'LIKE', '%adjustment%')
            ->first();

        if (!$adjustmentAccount) {
            $adjustmentAccount = \App\Models\Account::where('company_id', $company->id)
                ->where('type', 'expense')
                ->first();
        }

        // Debit adjustment account
        \App\Models\JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $adjustmentAccount->id,
            'debit' => $totalCost,
            'credit' => \Brick\Money\Money::of(0, $company->currency->code),
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Credit inventory account
        \App\Models\JournalEntryLine::create([
            'company_id' => $company->id,
            'journal_entry_id' => $journalEntry->id,
            'account_id' => $product->default_inventory_account_id,
            'debit' => \Brick\Money\Money::of(0, $company->currency->code),
            'credit' => $totalCost,
            'description' => "Inventory adjustment - {$product->name}",
        ]);

        // Create StockMoveValuation record to link the move with the journal entry
        \App\Models\StockMoveValuation::create([
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
