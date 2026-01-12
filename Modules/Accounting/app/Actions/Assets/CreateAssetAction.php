<?php

namespace Modules\Accounting\Actions\Assets;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\Assets\AssetStatus;
use Modules\Accounting\Models\Asset;

class CreateAssetAction
{
    public function execute(\Modules\Accounting\DataTransferObjects\Assets\CreateAssetDTO $dto): Asset
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
                'prorata_temporis' => $dto->prorata_temporis,
                'declining_factor' => $dto->declining_factor,
                'source_type' => $dto->source_type,
                'source_id' => $dto->source_id,
            ]);
        });
    }
}
