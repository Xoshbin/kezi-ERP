<?php

namespace App\DataTransferObjects\Payments;

use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Models\Payment;
use Brick\Money\Money;

class UpdatePaymentDTO
{
    /**
     * @param  UpdatePaymentDocumentLinkDTO[]  $document_links
     */
    public function __construct(
        public readonly Payment $payment,
        public readonly int $company_id,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly PaymentPurpose $payment_purpose,
        public readonly PaymentType $payment_type,
        public readonly PaymentMethod $payment_method,
        public readonly ?int $partner_id,
        public readonly ?Money $amount,
        public readonly ?int $counterpart_account_id,
        public readonly array $document_links,
        public readonly ?string $reference,
        public readonly int $updated_by_user_id
    ) {}
}
