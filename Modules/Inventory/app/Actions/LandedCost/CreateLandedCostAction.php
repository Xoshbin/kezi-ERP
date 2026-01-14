<?php

namespace Modules\Inventory\Actions\LandedCost;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DataTransferObjects\LandedCost\LandedCostData;
use Modules\Inventory\Models\LandedCost;

class CreateLandedCostAction
{
    public function execute(LandedCostData $dto): LandedCost
    {
        return DB::transaction(function () use ($dto) {
            return LandedCost::create([
                'company_id' => $dto->company->id,
                'date' => $dto->date,
                'amount_total' => $dto->amount_total,
                'allocation_method' => $dto->allocation_method,
                'description' => $dto->description,
                'vendor_bill_id' => $dto->vendor_bill_id,
                'created_by_user_id' => $dto->created_by_user?->id,
                'status' => $dto->status,
            ]);
        });
    }
}
