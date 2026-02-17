<?php

namespace Kezi\Pos\DataTransferObjects;

use Illuminate\Support\Collection;

class PosOrderData
{
    public function __construct(
        public string $uuid,
        public string $order_number,
        public string $status,
        public string $ordered_at, // ISO String
        public string $total_amount, // Stringified integer (minor units)
        public string $total_tax, // Stringified integer (minor units)
        public string $discount_amount, // Stringified integer (minor units)
        public ?string $notes,
        public ?int $customer_id,
        public int $currency_id,
        public array $sector_data,
        public int $pos_session_id,
        public Collection $lines,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            order_number: $data['order_number'],
            status: $data['status'],
            ordered_at: $data['ordered_at'],
            total_amount: (string) $data['total_amount'],
            total_tax: (string) $data['total_tax'],
            discount_amount: (string) ($data['discount_amount'] ?? 0),
            notes: $data['notes'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            currency_id: $data['currency_id'],
            sector_data: $data['sector_data'] ?? [],
            pos_session_id: $data['pos_session_id'],
            lines: collect($data['lines'] ?? [])->map(fn ($l) => PosOrderLineData::from($l)),
        );
    }

    public static function collect(array|Collection $items): Collection
    {
        return collect($items)->map(fn ($item) => self::from($item));
    }
}
