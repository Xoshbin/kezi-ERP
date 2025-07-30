<?php

namespace App\DataTransferObjects\Payments;

class CreatePaymentDocumentLinkDTO
{
    public function __construct(
        public readonly string $document_type, // 'invoice' or 'vendor_bill'
        public readonly int $document_id,
        public readonly string $amount_applied,
    ) {}
}
