<?php

namespace Kezi\Payment\DataTransferObjects\Payments;

use Brick\Money\Money;

class UpdatePaymentDocumentLinkDTO
{
    public function __construct(
        public readonly string $document_type, // 'invoice' or 'vendor_bill'
        public readonly int $document_id,
        public readonly Money $amount_applied,
    ) {}
}
