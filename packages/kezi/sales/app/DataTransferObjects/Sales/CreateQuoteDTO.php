<?php

namespace Kezi\Sales\DataTransferObjects\Sales;

use Carbon\Carbon;

/**
 * Data Transfer Object for creating a new Quote
 */
readonly class CreateQuoteDTO
{
    /**
     * @param  array<CreateQuoteLineDTO>  $lines
     */
    public function __construct(
        public int $companyId,
        public int $partnerId,
        public int $currencyId,
        public Carbon $quoteDate,
        public Carbon $validUntil,
        public array $lines = [],
        public ?string $notes = null,
        public ?string $termsAndConditions = null,
        public float $exchangeRate = 1.0,
        public ?int $createdByUserId = null,
    ) {}
}
