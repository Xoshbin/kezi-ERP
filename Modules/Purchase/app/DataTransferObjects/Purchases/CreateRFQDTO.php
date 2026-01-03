<?php

namespace Modules\Purchase\DataTransferObjects\Purchases;

use Carbon\Carbon;

readonly class CreateRFQDTO
{
    /**
     * @param  array<CreateRFQLineDTO>  $lines
     */
    public function __construct(
        public int $companyId,
        public int $vendorId,
        public int $currencyId,
        public Carbon $rfqDate,
        public ?Carbon $validUntil = null,
        public ?string $notes = null,
        public float $exchangeRate = 1.0,
        public ?int $createdByUserId = null,
        public array $lines = [],
    ) {}
}
