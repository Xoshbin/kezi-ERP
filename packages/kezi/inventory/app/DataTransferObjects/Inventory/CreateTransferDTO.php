<?php

namespace Kezi\Inventory\DataTransferObjects\Inventory;

use Carbon\Carbon;

/**
 * DTO for creating a new internal transfer.
 */
readonly class CreateTransferDTO
{
    /**
     * @param  array<CreateTransferLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $source_location_id,
        public int $destination_location_id,
        public ?int $transit_location_id = null,
        public ?Carbon $scheduled_date = null,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?int $created_by_user_id = null,
        public array $lines = [],
    ) {}
}
