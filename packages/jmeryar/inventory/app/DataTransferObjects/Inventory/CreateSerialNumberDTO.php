<?php

namespace Jmeryar\Inventory\DataTransferObjects\Inventory;

readonly class CreateSerialNumberDTO
{
    public function __construct(
        public int $company_id,
        public int $product_id,
        public string $serial_code,
        public ?int $current_location_id = null,
        public ?\DateTimeInterface $warranty_start = null,
        public ?\DateTimeInterface $warranty_end = null,
        public ?string $notes = null,
    ) {}
}
