<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use Carbon\Carbon;

/**
 * Data Transfer Object for updating an existing Quote
 */
readonly class UpdateQuoteDTO
{
    /**
     * @param  array<UpdateQuoteLineDTO>  $lines
     */
    public function __construct(
        public int $quoteId,
        public ?int $partnerId = null,
        public ?int $currencyId = null,
        public ?Carbon $quoteDate = null,
        public ?Carbon $validUntil = null,
        public ?string $notes = null,
        public ?string $termsAndConditions = null,
        public ?float $exchangeRate = null,
        public ?array $lines = null,
    ) {}
}
