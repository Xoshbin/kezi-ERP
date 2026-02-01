<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

/**
 * DTO for receiving a transfer order (moving stock from transit to destination).
 */
readonly class ReceiveTransferDTO
{
    /**
     * @param  array<ReceiveTransferLineDTO>  $lines  Optional partial receipt lines
     */
    public function __construct(
        public int $stock_picking_id,
        public int $received_by_user_id,
        public array $lines = [],
    ) {}
}
