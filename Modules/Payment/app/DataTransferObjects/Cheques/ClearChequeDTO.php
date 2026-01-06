<?php

namespace Modules\Payment\DataTransferObjects\Cheques;

readonly class ClearChequeDTO
{
    public function __construct(
        public int $cheque_id,
        public string $cleared_at,
    ) {}
}
