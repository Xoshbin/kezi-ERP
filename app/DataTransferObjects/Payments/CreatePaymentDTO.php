<?php

namespace App\DataTransferObjects\Payments;

use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use Brick\Money\Money;

class CreatePaymentDTO
{
    /**
     * @param CreatePaymentDocumentLinkDTO[] $document_links
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly PaymentPurpose $payment_purpose,
        public readonly PaymentType $payment_type,
        public readonly ?int $partner_id,
        public readonly ?Money $amount,
        public readonly ?int $counterpart_account_id,
        public readonly array $document_links,
        public readonly ?string $reference,
    ) {}
}
