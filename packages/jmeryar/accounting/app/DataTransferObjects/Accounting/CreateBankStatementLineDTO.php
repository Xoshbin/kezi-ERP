<?php

namespace Jmeryar\Accounting\DataTransferObjects\Accounting;

use Brick\Money\Money;
use InvalidArgumentException;

class CreateBankStatementLineDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $description,
        public readonly Money $amount,
        public readonly ?string $partner_id,
        public readonly ?int $foreign_currency_id = null,
        public readonly ?Money $amount_in_foreign_currency = null,
    ) {
        // Business rule validation: If a foreign currency is specified, the foreign amount must also be present.
        if ($this->foreign_currency_id && ! $this->amount_in_foreign_currency) {
            throw new InvalidArgumentException('Foreign amount is required when a foreign currency is specified.');
        }

        // Business rule validation: If a foreign amount is specified, the foreign currency must also be present.
        if ($this->amount_in_foreign_currency && ! $this->foreign_currency_id) {
            throw new InvalidArgumentException('Foreign currency is required when a foreign amount is specified.');
        }
    }
}
