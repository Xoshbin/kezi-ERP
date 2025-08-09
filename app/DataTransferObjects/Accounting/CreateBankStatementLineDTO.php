<?php

namespace App\DataTransferObjects\Accounting;

use Brick\Money\Money;

class CreateBankStatementLineDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $description,
        public readonly Money $amount,
        public readonly ?string $partner_id,
    ) {}
}
