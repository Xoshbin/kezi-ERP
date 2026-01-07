<?php

namespace Modules\Payment\DataTransferObjects\PettyCash;

use Brick\Money\Money;

readonly class CreatePettyCashVoucherDTO
{
    public function __construct(
        public int $company_id,
        public int $fund_id,
        public int $expense_account_id,
        public Money $amount,
        public string $voucher_date,
        public string $description,
        public ?int $partner_id = null,
        public ?string $receipt_reference = null,
    ) {}
}
