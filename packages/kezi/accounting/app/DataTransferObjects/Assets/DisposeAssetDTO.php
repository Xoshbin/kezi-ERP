<?php

namespace Kezi\Accounting\DataTransferObjects\Assets;

use Brick\Money\Money;
use Carbon\Carbon;

readonly class DisposeAssetDTO
{
    public function __construct(
        public Carbon $disposal_date,
        public Money $disposal_value,
        public int $gain_loss_account_id,
    ) {}
}
