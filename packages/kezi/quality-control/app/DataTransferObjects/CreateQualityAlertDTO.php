<?php

namespace Kezi\QualityControl\DataTransferObjects;

readonly class CreateQualityAlertDTO
{
    public function __construct(
        public int $companyId,
        public ?int $qualityCheckId,
        public int $productId,
        public ?int $lotId,
        public ?int $serialNumberId,
        public ?int $defectTypeId,
        public string $description,
        public int $reportedByUserId,
        public ?int $assignedToUserId = null,
    ) {}
}
