<?php

namespace Kezi\Pos\DataTransferObjects;

use Illuminate\Support\Collection;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Pos\Enums\PosOrderStatus;

class PosOrderData
{
    /**
     * @param  Collection<int, PosOrderLineData>  $lines
     * @param  Collection<int, PosOrderPaymentData>  $payments
     */
    public function __construct(
        public string $uuid,
        public string $order_number,
        public PosOrderStatus $status,
        public PaymentMethod $payment_method,
        public string $ordered_at, // ISO String
        public string $total_amount, // Stringified integer (minor units)
        public string $total_tax, // Stringified integer (minor units)
        public string $discount_amount, // Stringified integer (minor units)
        public ?string $notes,
        public ?int $customer_id,
        public int $currency_id,
        /** @var array<int, mixed> */
        public array $sector_data,
        public int $pos_session_id,
        /** @var Collection<int, PosOrderLineData> */
        public Collection $lines,
        /** @var Collection<int, PosOrderPaymentData> */
        public Collection $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        // Infer primary payment_method from payments array if not explicitly provided
        $paymentsData = PosOrderPaymentData::collect($data['payments'] ?? []);

        $paymentMethod = ($data['payment_method'] ?? null) instanceof PaymentMethod
            ? $data['payment_method']
            : (PaymentMethod::tryFrom($data['payment_method'] ?? '') ?? null);

        // Fallback: if no explicit payment_method, derive from the first payment
        if (! $paymentMethod) {
            $firstPayment = $paymentsData->first();
            $paymentMethod = $firstPayment !== null ? $firstPayment->method : PaymentMethod::Cash;
        }

        /** @var array<int, array<string, mixed>> $rawLines */
        $rawLines = $data['lines'] ?? [];

        return new self(
            uuid: $data['uuid'],
            order_number: $data['order_number'],
            status: PosOrderStatus::tryFrom($data['status'] ?? '') ?? PosOrderStatus::Paid,
            payment_method: $paymentMethod,
            ordered_at: $data['ordered_at'],
            total_amount: (string) $data['total_amount'],
            total_tax: (string) $data['total_tax'],
            discount_amount: (string) ($data['discount_amount'] ?? 0),
            notes: $data['notes'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            currency_id: $data['currency_id'],
            sector_data: $data['sector_data'] ?? [],
            pos_session_id: $data['pos_session_id'],
            lines: collect($rawLines)->map(fn ($l) => PosOrderLineData::from((array) $l)),
            payments: $paymentsData,
        );
    }

    /**
     * @param  array<array-key, array<string, mixed>>|Collection<int, self>  $items
     * @return Collection<int, self>
     */
    public static function collect(array|Collection $items): Collection
    {
        return collect($items)->map(fn ($item) => self::from((array) $item));
    }
}
