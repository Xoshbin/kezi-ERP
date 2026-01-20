<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Actions\CreateBOMAction;
use Modules\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Modules\Manufacturing\Models\BillOfMaterial;

class BOMService
{
    public function __construct(
        private readonly CreateBOMAction $createBOMAction,
    ) {}

    public function create(CreateBOMDTO $dto): BillOfMaterial
    {
        // Validate that finished product is not in component list
        foreach ($dto->lines as $line) {
            if ($line->productId === $dto->productId) {
                throw new \InvalidArgumentException('A product cannot be a component of itself in a BOM.');
            }
        }

        return $this->createBOMAction->execute($dto);
    }

    /**
     * @param  int[]  $processedBoms
     */
    public function calculateTotalMaterialCost(BillOfMaterial $bom, array $processedBoms = []): \Brick\Money\Money
    {
        if (in_array($bom->id, $processedBoms)) {
            throw new \RuntimeException('Circular BOM dependency detected');
        }

        $processedBoms[] = $bom->id;
        $currencyCode = $bom->company->currency->code;
        $total = \Brick\Money\Money::zero($currencyCode);

        foreach ($bom->lines as $line) {
            $component = $line->product;
            $lineQuantity = $line->quantity;

            if (! $component) {
                continue;
            }

            // Check if component has an active BOM
            $componentBom = BillOfMaterial::where('product_id', $component->id)
                ->where('company_id', $bom->company_id)
                ->where('is_active', true)
                ->with(['lines.product', 'company.currency'])
                ->first();

            if ($componentBom) {
                // Recursive calculation: (innerBOMCost / innerBOMQuantity) * lineQuantity
                $innerCost = $this->calculateTotalMaterialCost($componentBom, $processedBoms);
                $unitCost = $innerCost->dividedBy($componentBom->quantity, \Brick\Math\RoundingMode::HALF_UP);
                $lineCost = $unitCost->multipliedBy($lineQuantity, \Brick\Math\RoundingMode::HALF_UP);
            } else {
                // Fallback to average_cost, then to unit_cost on line if average_cost is 0
                $unitCost = $component->average_cost;
                if (! $unitCost || $unitCost->isZero()) {
                    $unitCost = $line->unit_cost;
                }
                $lineCost = $unitCost->multipliedBy($lineQuantity, \Brick\Math\RoundingMode::HALF_UP);
            }

            $total = $total->plus($lineCost);
        }

        return $total;
    }
}
