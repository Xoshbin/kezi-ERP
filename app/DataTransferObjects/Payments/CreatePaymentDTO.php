<?php

namespace App\DataTransferObjects\Payments;

use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentType;
use Brick\Money\Money;

class CreatePaymentDTO
{
    /**
     * @param  CreatePaymentDocumentLinkDTO[]  $document_links
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly PaymentType $payment_type,
        public readonly PaymentMethod $payment_method,
        public readonly ?int $paid_to_from_partner_id,
        public readonly ?Money $amount,
        public readonly array $document_links,
        public readonly ?string $reference,
    ) {}
}
