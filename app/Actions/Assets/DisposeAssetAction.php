<?php

namespace App\Actions\Assets;

use App\DataTransferObjects\Assets\DisposeAssetDTO;
use App\Enums\Assets\AssetStatus;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class DisposeAssetAction
{
    public function execute(Asset $asset, DisposeAssetDTO $dto): Asset
    {
        return DB::transaction(function () use ($asset, $dto) {
            // Note: Full journal entry logic will be added later.
            // This action currently only updates the asset's status.
            $asset->update([
                'status' => AssetStatus::Sold,
                'disposal_date' => $dto->disposal_date,
                'disposal_price' => $dto->disposal_value,
            ]);

            return $asset;
        });
    }
}
