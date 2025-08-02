<?php

namespace App\Actions\Assets;

use App\DataTransferObjects\Assets\UpdateAssetDTO;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class UpdateAssetAction
{
    public function execute(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return DB::transaction(function () use ($asset, $dto) {
            $asset->update([
                'name' => $dto->name,
                'purchase_date' => $dto->purchase_date,
                'purchase_price' => $dto->purchase_value,
                'salvage_value' => $dto->salvage_value,
                'useful_life' => $dto->useful_life_years,
                'depreciation_method' => $dto->depreciation_method,
                'asset_account_id' => $dto->asset_account_id,
                'depreciation_expense_account_id' => $dto->depreciation_expense_account_id,
                'accumulated_depreciation_account_id' => $dto->accumulated_depreciation_account_id,
                'currency_id' => $dto->currency_id,
            ]);

            return $asset;
        });
    }
}
