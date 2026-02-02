<?php

namespace Kezi\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\Manufacturing\DataTransferObjects\CreateBOMDTO;
use Kezi\Manufacturing\Models\BillOfMaterial;

class CreateBOMAction
{
    public function execute(CreateBOMDTO $dto): BillOfMaterial
    {
        return DB::transaction(function () use ($dto) {
            // Create the BOM
            $bom = BillOfMaterial::create([
                'company_id' => $dto->companyId,
                'product_id' => $dto->productId,
                'code' => $dto->code,
                'name' => $dto->name,
                'type' => $dto->type,
                'quantity' => $dto->quantity,
                'is_active' => $dto->isActive,
                'notes' => $dto->notes,
            ]);

            // Create BOM lines
            foreach ($dto->lines as $lineDTO) {
                $bom->lines()->create([
                    'company_id' => $dto->companyId,
                    'product_id' => $lineDTO->productId,
                    'quantity' => $lineDTO->quantity,
                    'unit_cost' => $lineDTO->unitCost, // Let the MoneyCast handle conversion
                    'currency_code' => $lineDTO->unitCost->getCurrency()->getCurrencyCode(),
                    'work_center_id' => $lineDTO->workCenterId,
                ]);
            }

            return $bom->fresh(['lines']);
        });
    }
}
