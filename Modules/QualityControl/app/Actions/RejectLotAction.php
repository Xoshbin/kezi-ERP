<?php

namespace Modules\QualityControl\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Lot;
use Modules\QualityControl\DataTransferObjects\RejectLotDTO;

class RejectLotAction
{
    public function execute(RejectLotDTO $dto): Lot
    {
        return DB::transaction(function () use ($dto) {
            $lot = Lot::findOrFail($dto->lotId);

            // Update lot with rejection data
            $lot->update([
                'is_rejected' => 1,
                'rejection_reason' => $dto->rejectionReason,
                'quarantine_location_id' => $dto->quarantineLocationId,
                'active' => false, // Deactivate rejected lots
            ]);

            // If quarantine location is specified, move stock to quarantine
            // This would require integration with StockMoveService
            // For now, just mark the lot as rejected

            return $lot->fresh(['quarantineLocation']);
        });
    }
}
