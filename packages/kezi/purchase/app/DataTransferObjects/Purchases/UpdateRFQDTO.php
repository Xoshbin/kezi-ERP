<?php

namespace Kezi\Purchase\DataTransferObjects\Purchases;

use Carbon\Carbon;

readonly class UpdateRFQDTO
{
    /**
     * @param  array<CreateRFQLineDTO>  $lines
     */
    public function __construct(
        public int $rfqId,
        public ?\Kezi\Purchase\Models\RequestForQuotation $rfq = null,
        public ?int $vendorId = null,
        public ?int $currencyId = null,
        public ?Carbon $rfqDate = null,
        public ?Carbon $validUntil = null,
        public ?string $notes = null,
        public ?float $exchangeRate = null,
        public ?array $lines = null,
    ) {}
}
