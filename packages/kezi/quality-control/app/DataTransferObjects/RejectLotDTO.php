<?php

namespace Kezi\QualityControl\DataTransferObjects;

readonly class RejectLotDTO
{
    public function __construct(
        public int $lotId,
        public string $rejectionReason,
        public ?int $quarantineLocationId = null,
    ) {}
}
