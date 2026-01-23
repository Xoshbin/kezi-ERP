<?php

namespace Modules\QualityControl\DataTransferObjects;

readonly class CreateQualityCheckDTO
{
    public function __construct(
        public int $companyId,
        public string $sourceType,
        public int $sourceId,
        public int $productId,
        public ?int $lotId = null,
        public ?int $serialNumberId = null,
        public ?int $inspectionTemplateId = null,
        public ?string $notes = null,
        public bool $isBlocking = false,
    ) {}
}
