<?php

namespace Modules\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Foundation\Services\SequenceService;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Enums\BOMType;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;

class CreateManufacturingOrderAction
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    ) {}

    public function execute(CreateManufacturingOrderDTO $dto): ManufacturingOrder
    {
        return DB::transaction(function () use ($dto) {
            // Get the BOM to copy its lines
            $bom = BillOfMaterial::with('lines')->findOrFail($dto->bomId);

            // Generate MO number
            $company = \App\Models\Company::findOrFail($dto->companyId);
            $number = $this->sequenceService->getNextNumber($company, 'manufacturing_order', 'MO', 5);

            // Create the Manufacturing Order
            $mo = ManufacturingOrder::create([
                'company_id' => $dto->companyId,
                'number' => $number,
                'bom_id' => $dto->bomId,
                'product_id' => $dto->productId,
                'quantity_to_produce' => $dto->quantityToProduce,
                'quantity_produced' => 0,
                'status' => ManufacturingOrderStatus::Draft,
                'planned_start_date' => $dto->plannedStartDate,
                'planned_end_date' => $dto->plannedEndDate,
                'source_location_id' => $dto->sourceLocationId,
                'destination_location_id' => $dto->destinationLocationId,
                'notes' => $dto->notes,
            ]);

            // Create MO lines from BOM lines (with recursive explosion)
            $explodedLines = $this->recursiveExplode($bom, $dto->quantityToProduce, 1);

            foreach ($explodedLines as $line) {
                $mo->lines()->create([
                    'company_id' => $dto->companyId,
                    'product_id' => $line['product_id'],
                    'quantity_required' => $line['quantity'],
                    'quantity_consumed' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'currency_code' => $line['currency_code'],
                ]);
            }

            return $mo->fresh(['lines', 'billOfMaterial', 'product']);
        });
    }

    /**
     * @return array<int, array{product_id: int, quantity: float, unit_cost: mixed, currency_code: string}>
     */
    private function recursiveExplode(BillOfMaterial $bom, float $parentQuantity, int $depth): array
    {
        if ($depth > 10) {
            throw new \RuntimeException('Max BOM explosion depth reached (circular dependency?)');
        }

        $lines = [];

        foreach ($bom->lines as $bomLine) {
            $totalQty = $bomLine->quantity * $parentQuantity;

            // Check if this product has its own BOM
            $subBom = BillOfMaterial::with('lines')
                ->where('product_id', $bomLine->product_id)
                ->where('is_active', true)
                ->first();

            if ($subBom && in_array($subBom->type, [BOMType::Kit, BOMType::Phantom])) {
                // If it's a Kit or Phantom, explode it
                $subLines = $this->recursiveExplode($subBom, $totalQty, $depth + 1);
                foreach ($subLines as $subLine) {
                    $lines[] = $subLine;
                }
            } else {
                // Normal raw material or sub-assembly (Normal BOM type)
                $lines[] = [
                    'product_id' => $bomLine->product_id,
                    'quantity' => $totalQty,
                    'unit_cost' => $bomLine->unit_cost,
                    'currency_code' => $bomLine->currency_code,
                ];
            }
        }

        return $lines;
    }
}
