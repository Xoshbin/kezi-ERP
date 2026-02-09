<?php

namespace Kezi\Accounting\DataTransferObjects\Assets;

use Brick\Money\Money;
use Carbon\Carbon;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;

readonly class UpdateAssetDTO
{
    public function __construct(
        public string $name,
        public Carbon $purchase_date,
        public Money $purchase_value,
        public Money $salvage_value,
        public int $useful_life_years,
        public DepreciationMethod $depreciation_method,
        public int $asset_account_id,
        public int $depreciation_expense_account_id,
        public int $accumulated_depreciation_account_id,
        public int $currency_id,
    ) {}
}
