<?php

namespace Kezi\Accounting\DataTransferObjects\Assets;

use Brick\Money\Money;
use Carbon\Carbon;
use Kezi\Accounting\Enums\Assets\DepreciationMethod;

readonly class CreateAssetDTO
{
    public function __construct(
        public int $company_id,
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
        public bool $prorata_temporis = false,
        public ?float $declining_factor = null,
        public ?string $source_type = null,
        public ?int $source_id = null,
    ) {}
}
