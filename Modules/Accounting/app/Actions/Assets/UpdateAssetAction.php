<?php

namespace Modules\Accounting\Actions\Assets;

use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Assets\UpdateAssetDTO;
use Modules\Accounting\Models\Asset;

class UpdateAssetAction
{
    public function __construct(
        protected ComputeDepreciationScheduleAction $computeDepreciationScheduleAction,
    ) {}

    public function execute(Asset $asset, UpdateAssetDTO $dto): Asset
    {
        return DB::transaction(function () use ($asset, $dto) {
            $asset->update([
                'name' => $dto->name,
                'purchase_date' => $dto->purchase_date,
                'purchase_value' => $dto->purchase_value,
                'salvage_value' => $dto->salvage_value,
                'useful_life_years' => $dto->useful_life_years,
                'depreciation_method' => $dto->depreciation_method,
                'asset_account_id' => $dto->asset_account_id,
                'depreciation_expense_account_id' => $dto->depreciation_expense_account_id,
                'accumulated_depreciation_account_id' => $dto->accumulated_depreciation_account_id,
                'currency_id' => $dto->currency_id,
            ]);

            // After updating the asset, re-compute the future depreciation schedule.
            $this->computeDepreciationScheduleAction->execute($asset);

            return $asset;
        });
    }
}
