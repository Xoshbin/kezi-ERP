<?php

namespace Kezi\Pos\DataTransferObjects;

use Illuminate\Support\Collection;
use Kezi\Payment\Enums\Payments\PaymentMethod;

class PosOrderPaymentData
{
    public function __construct(
        public readonly PaymentMethod $method,
        public readonly int $amount,
        public readonly ?int $amount_tendered,
        public readonly int $change_given,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            method: (($data['method'] ?? null) instanceof PaymentMethod)
                ? $data['method']
                : (PaymentMethod::tryFrom($data['method'] ?? '') ?? PaymentMethod::Cash),
            amount: (int) $data['amount'],
            amount_tendered: isset($data['amount_tendered']) ? (int) $data['amount_tendered'] : null,
            change_given: (int) ($data['change_given'] ?? 0),
        );
    }

    /**
     * @param  array<array-key, array<string, mixed>>|Collection<int, self>  $items
     * @return Collection<int, self>
     */
    public static function collect(array|Collection $items): Collection
    {
        return collect($items)->map(fn ($item) => self::from($item));
    }
}
