<?php

namespace App\DataTransferObjects\Assets;

use Carbon\Carbon;

readonly class DisposeAssetDTO
{
    public function __construct(
        public Carbon $disposal_date,
        public int $disposal_value,
        public int $gain_loss_account_id,
    ) {
    }
}
