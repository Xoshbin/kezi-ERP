<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

use Carbon\Carbon;

readonly class ConvertRFQToPurchaseOrderDTO
{
    public function __construct(
        public int $rfqId,
        public Carbon $poDate,
        public ?Carbon $expectedDeliveryDate = null,
        public ?string $reference = null, // Vendor reference/order acknowledgment
        public ?string $notes = null,
        public ?int $convertedByUserId = null,
    ) {}
}
