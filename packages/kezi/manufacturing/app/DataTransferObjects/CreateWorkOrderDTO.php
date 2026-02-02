<?php

namespace Kezi\Manufacturing\DataTransferObjects;

readonly class CreateWorkOrderDTO
{
    public function __construct(
        public int $companyId,
        public int $manufacturingOrderId,
        public int $workCenterId,
        public string $name,
        public int $sequence = 1,
        public ?float $plannedDuration = null,
    ) {}
}
