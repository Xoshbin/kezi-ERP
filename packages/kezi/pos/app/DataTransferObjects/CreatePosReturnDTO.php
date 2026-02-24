<?php

namespace Kezi\Pos\DataTransferObjects;

readonly class CreatePosReturnDTO
{
    /**
     * @param  array<CreatePosReturnLineDTO>  $lines
     */
    public function __construct(
        public int $company_id,
        public int $pos_session_id,
        public int $original_order_id,
        public int $currency_id,
        public \DateTimeInterface $return_date,
        public string $return_reason,
        public ?string $return_notes,
        public int $requested_by_user_id,
        public ?string $refund_method,
        public array $lines,
    ) {}
}
