<?php

namespace App\DataTransferObjects\Payments;

use App\Models\Payment;

class UpdatePaymentDTO
{
    /**
     * @param UpdatePaymentDocumentLinkDTO[] $document_links
     */
    public function __construct(
        public readonly Payment $payment,
        public readonly int $company_id,
        public readonly int $journal_id,
        public readonly int $currency_id,
        public readonly string $payment_date,
        public readonly array $document_links,
        public readonly ?string $reference,
        public readonly int $updated_by_user_id
    ) {}
}
