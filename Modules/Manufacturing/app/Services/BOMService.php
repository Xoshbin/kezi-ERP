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

    public function calculateTotalMaterialCost(BillOfMaterial $bom): \Brick\Money\Money
    {
        $total = \Brick\Money\Money::zero($bom->lines->first()?->currency_code ?? 'USD');

        foreach ($bom->lines as $line) {
            /** @var \Brick\Money\Money $unitCost */
            $unitCost = $line->unit_cost;
            $lineCost = $unitCost->multipliedBy($line->quantity);
            $total = $total->plus($lineCost);
        }

        return $total;
    }
}
