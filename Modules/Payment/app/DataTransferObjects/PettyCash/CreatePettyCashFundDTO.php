<?php

namespace Modules\Payment\DataTransferObjects\PettyCash;

use Brick\Money\Money;

readonly class CreatePettyCashFundDTO
{
    public function __construct(
        public int $company_id,
        public string $name,
        public int $custodian_id,
        public int $account_id,
        public int $bank_account_id,
        public int $currency_id,
        public Money $imprest_amount,
    ) {}
}
