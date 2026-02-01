<?php

namespace Jmeryar\Manufacturing\DataTransferObjects;

use Carbon\Carbon;

readonly class CreateManufacturingOrderDTO
{
    public function __construct(
        public int $companyId,
        public int $bomId,
        public int $productId,
        public float $quantityToProduce,
        public int $sourceLocationId,
        public int $destinationLocationId,
        public ?Carbon $plannedStartDate = null,
        public ?Carbon $plannedEndDate = null,
        public ?string $notes = null,
    ) {}
}
