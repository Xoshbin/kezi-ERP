<?php

namespace Kezi\Pos\DataTransferObjects;

readonly class SearchPosOrdersDTO
{
    public function __construct(
        public int $company_id,
        public ?string $order_number = null,
        public bool $exact_match = false,
        public ?\DateTimeInterface $date_from = null,
        public ?\DateTimeInterface $date_to = null,
        public ?int $customer_id = null,
        public ?string $customer_name = null,
        public ?int $amount_min = null,
        public ?int $amount_max = null,
        public ?string $payment_method = null,
        public ?string $status = null,
        public ?int $product_id = null,
        public ?string $product_search = null,
        public ?int $session_id = null,
        public ?int $per_page = 20,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            company_id: $data['company_id'],
            order_number: $data['order_number'] ?? null,
            exact_match: (bool) ($data['exact_match'] ?? false),
            date_from: isset($data['date_from']) ? new \DateTime($data['date_from']) : null,
            date_to: isset($data['date_to']) ? new \DateTime($data['date_to']) : null,
            customer_id: $data['customer_id'] ?? null,
            customer_name: $data['customer_name'] ?? null,
            amount_min: $data['amount_min'] ?? null,
            amount_max: $data['amount_max'] ?? null,
            payment_method: $data['payment_method'] ?? null,
            status: $data['status'] ?? null,
            product_id: $data['product_id'] ?? null,
            product_search: $data['product_search'] ?? null,
            session_id: $data['session_id'] ?? null,
            per_page: $data['per_page'] ?? 20,
        );
    }
}
