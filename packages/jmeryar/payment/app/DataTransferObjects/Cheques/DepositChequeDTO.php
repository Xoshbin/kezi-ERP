<?php

namespace Jmeryar\Payment\DataTransferObjects\Cheques;

readonly class DepositChequeDTO
{
    public function __construct(
        public int $cheque_id,
        public string $deposited_at,
    ) {}
}
