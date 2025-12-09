<?php

namespace Modules\Accounting\DataTransferObjects\Assets;

use Carbon\Carbon;
use Modules\Accounting\Enums\Assets\DepreciationMethod;

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
