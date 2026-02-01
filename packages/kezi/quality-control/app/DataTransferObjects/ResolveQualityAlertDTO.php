<?php

namespace Kezi\QualityControl\DataTransferObjects;

readonly class ResolveQualityAlertDTO
{
    public function __construct(
        public int $qualityAlertId,
        public string $rootCause,
        public string $correctiveAction,
        public string $preventiveAction,
        public bool $scrapItems = false,
        public ?int $resolvedByUserId = null,
    ) {}
}
