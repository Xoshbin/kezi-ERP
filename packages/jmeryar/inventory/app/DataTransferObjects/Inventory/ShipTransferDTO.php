<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

/**
 * DTO for shipping a transfer order (moving stock to transit location).
 */
readonly class ShipTransferDTO
{
    /**
     * @param  array<ShipTransferLineDTO>  $lines  Optional partial shipment lines
     */
    public function __construct(
        public int $stock_picking_id,
        public int $shipped_by_user_id,
        public array $lines = [],
    ) {}
}
