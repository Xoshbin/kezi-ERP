<?php

namespace App\DataTransferObjects\Accounting;

use App\Models\BankStatement;
use Brick\Money\Money;

class UpdateBankStatementDTO
{
    /**
     * @param  UpdateBankStatementLineDTO[]  $lines
     */
    public function __construct(
        public readonly BankStatement $bankStatement,
        public readonly int $currency_id,
        public readonly int $journal_id,
        public readonly string $reference,
        public readonly string $date,
        public readonly Money $starting_balance,
        public readonly Money $ending_balance,
        public readonly array $lines,
    ) {}
}
