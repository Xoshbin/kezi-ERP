<?php

namespace App\DataTransferObjects\Assets;

use App\Enums\Assets\DepreciationMethod;
use Carbon\Carbon;

readonly class CreateAssetDTO
{
    public function __construct(
        public int $company_id,
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
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {}
}
