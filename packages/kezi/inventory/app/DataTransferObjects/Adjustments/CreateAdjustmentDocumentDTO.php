<?php

namespace Kezi\Inventory\DataTransferObjects\Adjustments;

use Kezi\Inventory\Enums\Adjustments\AdjustmentDocumentType as AdjustmentDocumentTypeEnum;

class CreateAdjustmentDocumentDTO
{
    /**
     * @param  CreateAdjustmentDocumentLineDTO[]  $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly AdjustmentDocumentTypeEnum $type,
        public readonly string $date,
        public readonly string $reference_number,
        public readonly string $reason,
        public readonly int $currency_id,
        public readonly ?int $original_invoice_id,
        public readonly ?int $original_vendor_bill_id,
        public readonly array $lines = [], // Add this property
    ) {}
}
