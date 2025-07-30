<?php

namespace App\DataTransferObjects\Payments;

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
        public readonly array $document_links,
        public readonly ?string $reference,
    ) {}
}
