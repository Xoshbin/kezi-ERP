<?php

namespace App\DataTransferObjects\Assets;

use App\Enums\Assets\DepreciationMethod;
use Carbon\Carbon;

readonly class UpdateAssetDTO
{
    public function __construct(
        public string $name,
        public Carbon $purchase_date,
        public int $purchase_value,
        public int $salvage_value,
        public int $useful_life_years,
        public DepreciationMethod $depreciation_method,
        public int $asset_account_id,
        public int $depreciation_expense_account_id,
        public int $accumulated_depreciation_account_id,
        public int $currency_id,
    ) {}
}
