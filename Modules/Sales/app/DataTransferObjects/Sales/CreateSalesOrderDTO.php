<?php

namespace Modules\Sales\DataTransferObjects\Sales;

use Carbon\Carbon;
use Modules\Foundation\Enums\Incoterm;

/**
 * Data Transfer Object for creating a new Sales Order
 */
readonly class CreateSalesOrderDTO
{
    /**
     * @param  array<CreateSalesOrderLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $customer_id,
        public int $currency_id,
        public int $created_by_user_id,
        public ?string $reference = null,
        public Carbon $so_date = new Carbon,
        public ?Carbon $expected_delivery_date = null,
        public ?float $exchange_rate_at_creation = null,
        public ?string $notes = null,
        public ?string $terms_and_conditions = null,
        public ?int $delivery_location_id = null,
        public ?Incoterm $incoterm = null,
        public array $lines = [],
    ) {}
}
