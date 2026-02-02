<?php

namespace Kezi\Payment\DataTransferObjects\Cheques;

use Brick\Money\Money;

readonly class BounceChequeDTO
{
    public function __construct(
        public int $cheque_id,
        public string $bounced_at,
        public string $reason,
        public ?Money $bank_charges = null,
        public ?string $notes = null,
    ) {}
}
