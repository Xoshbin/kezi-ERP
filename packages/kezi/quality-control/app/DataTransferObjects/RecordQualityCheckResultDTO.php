<?php

namespace Kezi\QualityControl\DataTransferObjects;

readonly class RecordQualityCheckResultDTO
{
    /**
     * @param  array<int, array{parameter_id: int, result_pass_fail?: bool, result_numeric?: float, result_text?: string, result_image_path?: string}>  $lineResults
     */
    public function __construct(
        public int $qualityCheckId,
        public int $inspectedByUserId,
        public array $lineResults,
        public ?string $notes = null,
    ) {}
}
