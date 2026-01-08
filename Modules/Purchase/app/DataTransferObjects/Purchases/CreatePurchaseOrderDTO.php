<?php

namespace Modules\Purchase\DataTransferObjects\Purchases;

use Carbon\Carbon;
use Modules\Foundation\Enums\Incoterm;
use Modules\Foundation\Models\Currency;

/**
 * Data Transfer Object for creating a new Purchase Order
 */
readonly class CreatePurchaseOrderDTO
{
    /**
     * @param  array<CreatePurchaseOrderLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $vendor_id,
        public int $currency_id,
        public int $created_by_user_id,
        public ?string $reference = null,
        public Carbon $po_date = new Carbon,
        public ?Carbon $expected_delivery_date = null,
        public ?float $exchange_rate_at_creation = null,
        public ?string $notes = null,
        public ?string $terms_and_conditions = null,
        public ?int $delivery_location_id = null,
        public ?Incoterm $incoterm = null,
        public array $lines = [],
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $lines = [];
        if (isset($data['lines']) && is_array($data['lines'])) {
            // Get currency for line items
            $currency = null;
            if (isset($data['currency_id'])) {
                $currency = Currency::find($data['currency_id']);
            }
            $currencyCode = $currency?->code ?? 'USD';

            foreach ($data['lines'] as $lineData) {
                // Pass currency code to line DTO
                $lineData['currency'] = $currencyCode;
                $lines[] = CreatePurchaseOrderLineDTO::fromArray($lineData);
            }
        }

        return new self(
            company_id: $data['company_id'],
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            created_by_user_id: $data['created_by_user_id'],
            reference: $data['reference'] ?? null,
            po_date: isset($data['po_date']) ? Carbon::parse($data['po_date']) : new Carbon,
            expected_delivery_date: isset($data['expected_delivery_date']) ? Carbon::parse($data['expected_delivery_date']) : null,
            exchange_rate_at_creation: $data['exchange_rate_at_creation'] ?? null,
            notes: $data['notes'] ?? null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            delivery_location_id: $data['delivery_location_id'] ?? null,
            incoterm: isset($data['incoterm']) ? Incoterm::tryFrom($data['incoterm']) : null,
            lines: $lines,
        );
    }
}
