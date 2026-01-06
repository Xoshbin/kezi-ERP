<?php

namespace Modules\Accounting\DataTransferObjects\Accounting;

class CreateWithholdingTaxCertificateDTO
{
    /**
     * @param  array<int>  $entry_ids  - IDs of WithholdingTaxEntry records to include
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $vendor_id,
        public readonly string $certificate_date,
        public readonly string $period_start,
        public readonly string $period_end,
        public readonly int $currency_id,
        public readonly array $entry_ids,
        public readonly ?string $notes = null,
    ) {}
}
