<?php

namespace App\Actions\Assets;

use App\Enums\Assets\AssetStatus;
use App\DataTransferObjects\Assets\CreateAssetDTO;
use App\Models\Asset;
use Illuminate\Support\Facades\DB;

class CreateAssetAction
{
    public function execute(CreateAssetDTO $dto): Asset
    {
        return DB::transaction(function () use ($dto) {
            return Asset::create([
                'name' => $dto->name,
                'company_id' => $dto->company_id,
                'currency_id' => $dto->currency_id,
                'purchase_value' => $dto->purchase_value,
                'purchase_date' => $dto->purchase_date,
                'depreciation_method' => $dto->depreciation_method,
                'useful_life_years' => $dto->useful_life_years,
                'salvage_value' => $dto->salvage_value,
                'status' => AssetStatus::Draft,
                'asset_account_id' => $dto->asset_account_id,
                'accumulated_depreciation_account_id' => $dto->accumulated_depreciation_account_id,
                'depreciation_expense_account_id' => $dto->depreciation_expense_account_id,
                'source_type' => $dto->source_type,
                'source_id' => $dto->source_id,
            ]);
        });
    }
}
