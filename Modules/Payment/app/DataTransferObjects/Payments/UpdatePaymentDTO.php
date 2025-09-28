<?php

namespace Modules\Payment\DataTransferObjects\Payments;

use Brick\Money\Money;
use Modules\Payment\Models\Payment;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Enums\Payments\PaymentMethod;

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
        public readonly PaymentType $payment_type,
        public readonly PaymentMethod $payment_method,
        public readonly ?int $paid_to_from_partner_id,
        public readonly ?Money $amount,
        public readonly array $document_links,
        public readonly ?string $reference,
        public readonly int $updated_by_user_id,
    ) {}
}
