<?php

namespace App\DataTransferObjects\Accounting;

class CreateBankStatementLineDTO
{
    public function __construct(
        public readonly string $date,
        public readonly string $description,
        public readonly string $amount,
        public readonly ?string $partner_id,
    ) {}
}
