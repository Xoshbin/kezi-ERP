<?php

namespace App\DataTransferObjects\Accounting;

use Brick\Money\Money;

class UpdateBankStatementLineDTO
{
    public function __construct(
        public readonly ?int $id, // Null for new lines
        public readonly string $date,
        public readonly string $description,
        public readonly Money $amount,
        public readonly ?string $partner_id,
    ) {}
}
