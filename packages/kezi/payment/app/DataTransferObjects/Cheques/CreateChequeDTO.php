<?php

namespace Kezi\Payment\DataTransferObjects\Cheques;

use Brick\Money\Money;
use Kezi\Payment\Enums\Cheques\ChequeType;

readonly class CreateChequeDTO
{
    public function __construct(
        public int $company_id,
        public int $journal_id,
        public int $partner_id,
        public int $currency_id,
        public string $cheque_number,
        public Money $amount,
        public string $issue_date,
        public string $due_date,
        public ChequeType $type,
        public string $payee_name,
        public ?int $chequebook_id = null,
        public ?int $payment_id = null,
        public ?string $bank_name = null,
        public ?string $memo = null,
    ) {}
}
