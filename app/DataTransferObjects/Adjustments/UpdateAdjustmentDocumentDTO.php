<?php

namespace App\DataTransferObjects\Adjustments;

use App\Models\AdjustmentDocument;

class UpdateAdjustmentDocumentDTO
{
    /**
     * @param UpdateAdjustmentDocumentLineDTO[] $lines
     */
    public function __construct(
        public readonly AdjustmentDocument $adjustmentDocument,
        public readonly string $type,
        public readonly string $date,
        public readonly string $reference_number,
        public readonly string $reason,
        public readonly int $currency_id,
        public readonly ?int $original_invoice_id,
        public readonly ?int $original_vendor_bill_id,
        public readonly array $lines = [],
    ) {}
}
