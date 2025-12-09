<?php

namespace Modules\Inventory\DataTransferObjects\Adjustments;

use Modules\Inventory\Models\AdjustmentDocument;
use Modules\Inventory\Enums\Adjustments\AdjustmentDocumentType;

class UpdateAdjustmentDocumentDTO
{
    /**
     * @param  UpdateAdjustmentDocumentLineDTO[]  $lines
     */
    public function __construct(
        public readonly AdjustmentDocument $adjustmentDocument,
        public readonly AdjustmentDocumentType $type,
        public readonly string $date,
        public readonly string $reference_number,
        public readonly string $reason,
        public readonly int $currency_id,
        public readonly ?int $original_invoice_id,
        public readonly ?int $original_vendor_bill_id,
        public readonly array $lines = [],
    ) {}
}
