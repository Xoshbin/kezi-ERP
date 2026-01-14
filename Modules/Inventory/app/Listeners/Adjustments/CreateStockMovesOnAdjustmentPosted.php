<?php

namespace Modules\Inventory\Listeners\Adjustments;

use Illuminate\Support\Collection;
use Modules\Inventory\Actions\Inventory\CreateStockMoveAction;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO;
use Modules\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;
use Modules\Inventory\Enums\Inventory\StockLocationType;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockMoveType;
use Modules\Inventory\Events\AdjustmentDocumentPosted;
use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Inventory\Models\StockLocation;
use Modules\Product\Enums\Products\ProductType;

/**
 * Listener that creates stock moves when an adjustment document is posted.
 *
 * This handles the inventory impact of Credit Notes and Debit Notes:
 * - Credit Note (customer return): Move from Customer → Internal location
 * - Debit Note (vendor return): Move from Internal → Vendor location
 */
class CreateStockMovesOnAdjustmentPosted
{
    public function __construct(
        protected CreateStockMoveAction $createStockMoveAction,
    ) {}

    /**
     * Handle the AdjustmentDocumentPosted event.
     */
    public function handle(AdjustmentDocumentPosted $event): void
    {
        $adjustment = $event->adjustmentDocument;

        // Load lines with products
        $adjustment->load(['lines.product', 'company']);

        // Only process if there are storable products
        $storableLines = $adjustment->lines->filter(
            fn ($line) => $line->product && $line->product->type === ProductType::Storable
        );

        if ($storableLines->isEmpty()) {
            return;
        }

        match ($adjustment->type) {
            AdjustmentDocumentType::CreditNote => $this->createStockMovesForCreditNote($adjustment, $storableLines),
            AdjustmentDocumentType::DebitNote => $this->createStockMovesForDebitNote($adjustment, $storableLines),
            AdjustmentDocumentType::Miscellaneous => null, // No stock moves for miscellaneous adjustments
        };
    }

    /**
     * Create stock moves for Credit Note (customer returns goods).
     *
     * Moves products FROM Customer location TO Internal location.
     *
     * @param  Collection<int, \Modules\Inventory\Models\AdjustmentDocumentLine>  $storableLines
     */
    protected function createStockMovesForCreditNote(AdjustmentDocument $adjustment, Collection $storableLines): void
    {
        $locations = $this->getStockLocationsForCreditNote($adjustment);

        if (! $locations['customer'] || ! $locations['warehouse']) {
            return;
        }

        $productLines = $storableLines->map(fn ($line) => new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: (float) $line->quantity,
            from_location_id: $locations['customer']->id,
            to_location_id: $locations['warehouse']->id,
            description: "Credit Note return: {$line->description}",
            source_type: AdjustmentDocument::class,
            source_id: $adjustment->id
        ))->all();

        $dto = new CreateStockMoveDTO(
            company_id: $adjustment->company_id,
            product_lines: $productLines,
            move_type: StockMoveType::Incoming,
            status: StockMoveStatus::Done,
            move_date: $adjustment->posted_at ?? now(),
            reference: $adjustment->reference_number,
            description: "Credit Note return #{$adjustment->reference_number}",
            source_id: $adjustment->id,
            source_type: AdjustmentDocument::class,
            created_by_user_id: auth()->id() ?? 1,
        );

        $this->createStockMoveAction->execute($dto);
    }

    /**
     * Create stock moves for Debit Note (return goods to vendor).
     *
     * Moves products FROM Internal location TO Vendor location.
     *
     * @param  Collection<int, \Modules\Inventory\Models\AdjustmentDocumentLine>  $storableLines
     */
    protected function createStockMovesForDebitNote(AdjustmentDocument $adjustment, Collection $storableLines): void
    {
        $locations = $this->getStockLocationsForDebitNote($adjustment);

        if (! $locations['warehouse'] || ! $locations['vendor']) {
            return;
        }

        $productLines = $storableLines->map(fn ($line) => new CreateStockMoveProductLineDTO(
            product_id: $line->product_id,
            quantity: (float) $line->quantity,
            from_location_id: $locations['warehouse']->id,
            to_location_id: $locations['vendor']->id,
            description: "Debit Note return: {$line->description}",
            source_type: AdjustmentDocument::class,
            source_id: $adjustment->id
        ))->all();

        $dto = new CreateStockMoveDTO(
            company_id: $adjustment->company_id,
            product_lines: $productLines,
            move_type: StockMoveType::Outgoing,
            status: StockMoveStatus::Done,
            move_date: $adjustment->posted_at ?? now(),
            reference: $adjustment->reference_number,
            description: "Debit Note return #{$adjustment->reference_number}",
            source_id: $adjustment->id,
            source_type: AdjustmentDocument::class,
            created_by_user_id: auth()->id() ?? 1,
        );

        $this->createStockMoveAction->execute($dto);
    }

    /**
     * Get stock locations for Credit Note (customer return).
     *
     * @return array{customer: StockLocation|null, warehouse: StockLocation|null}
     */
    protected function getStockLocationsForCreditNote(AdjustmentDocument $adjustment): array
    {
        $company = $adjustment->company;

        /** @var StockLocation|null $warehouseLocation */
        $warehouseLocation = $company->defaultStockLocation
            ?? StockLocation::where('company_id', $company->id)
                ->where('type', StockLocationType::Internal)
                ->first();

        /** @var StockLocation|null $customerLocation */
        $customerLocation = StockLocation::where('company_id', $company->id)
            ->where('type', StockLocationType::Customer)
            ->first();

        return [
            'warehouse' => $warehouseLocation,
            'customer' => $customerLocation,
        ];
    }

    /**
     * Get stock locations for Debit Note (vendor return).
     *
     * @return array{warehouse: StockLocation|null, vendor: StockLocation|null}
     */
    protected function getStockLocationsForDebitNote(AdjustmentDocument $adjustment): array
    {
        $company = $adjustment->company;

        /** @var StockLocation|null $warehouseLocation */
        $warehouseLocation = $company->defaultStockLocation
            ?? StockLocation::where('company_id', $company->id)
                ->where('type', StockLocationType::Internal)
                ->first();

        /** @var StockLocation|null $vendorLocation */
        $vendorLocation = $company->vendorLocation
            ?? StockLocation::where('company_id', $company->id)
                ->where('type', StockLocationType::Vendor)
                ->first();

        return [
            'warehouse' => $warehouseLocation,
            'vendor' => $vendorLocation,
        ];
    }
}
