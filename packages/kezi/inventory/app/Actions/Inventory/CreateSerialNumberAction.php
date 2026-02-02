<?php

namespace Kezi\Inventory\Actions\Inventory;

use Illuminate\Support\Facades\DB;
use Kezi\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO;
use Kezi\Inventory\Enums\Inventory\SerialNumberStatus;
use Kezi\Inventory\Models\SerialNumber;

class CreateSerialNumberAction
{
    /**
     * Create a new serial number
     */
    public function execute(CreateSerialNumberDTO $dto): SerialNumber
    {
        return DB::transaction(function () use ($dto) {
            return SerialNumber::create([
                'company_id' => $dto->company_id,
                'product_id' => $dto->product_id,
                'serial_code' => $dto->serial_code,
                'status' => SerialNumberStatus::Available,
                'current_location_id' => $dto->current_location_id,
                'warranty_start' => $dto->warranty_start,
                'warranty_end' => $dto->warranty_end,
                'notes' => $dto->notes,
            ]);
        });
    }
}
