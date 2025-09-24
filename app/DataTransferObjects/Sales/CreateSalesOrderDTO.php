<?php

namespace App\DataTransferObjects\Sales;

use Carbon\Carbon;

/**
 * Data Transfer Object for creating a new Sales Order
 */
readonly class CreateSalesOrderDTO
{
    /**
     * @param int $company_id
     * @param int $customer_id
     * @param int $currency_id
     * @param int $created_by_user_id
     * @param string|null $reference
     * @param Carbon $so_date
     * @param Carbon|null $expected_delivery_date
     * @param float|null $exchange_rate_at_creation
     * @param string|null $notes
     * @param string|null $terms_and_conditions
     * @param int|null $delivery_location_id
     * @param array<CreateSalesOrderLineDTO> $lines
     */
    public function __construct(
        public int $company_id,
        public int $customer_id,
        public int $currency_id,
        public int $created_by_user_id,
        public ?string $reference = null,
        public Carbon $so_date = new Carbon(),
        public ?Carbon $expected_delivery_date = null,
        public ?float $exchange_rate_at_creation = null,
        public ?string $notes = null,
        public ?string $terms_and_conditions = null,
        public ?int $delivery_location_id = null,
        public array $lines = [],
    ) {}
}
