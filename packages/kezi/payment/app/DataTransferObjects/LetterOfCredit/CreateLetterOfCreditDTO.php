<?php

namespace Kezi\Payment\DataTransferObjects\LetterOfCredit;

use Brick\Money\Money;
use Illuminate\Support\Carbon;

readonly class CreateLetterOfCreditDTO
{
    public function __construct(
        public int $company_id,
        public int $vendor_id,
        public ?int $issuing_bank_partner_id,
        public int $currency_id,
        public ?int $purchase_order_id,
        public int $created_by_user_id,
        public Money $amount,
        public Money $amount_company_currency,
        public Carbon $issue_date,
        public Carbon $expiry_date,
        public ?Carbon $shipment_date,
        public string $type,
        public ?string $incoterm,
        public ?string $terms_and_conditions,
        public ?string $notes,
    ) {}
}
