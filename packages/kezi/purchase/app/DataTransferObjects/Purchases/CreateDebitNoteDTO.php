<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

class CreateDebitNoteDTO
{
    /**
     * @param  CreateDebitNoteLineDTO[]  $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $vendor_bill_id,
        public readonly string $date,
        public readonly string $reason,
        public readonly array $lines,
        public readonly ?string $reference_number = null,
    ) {}
}
