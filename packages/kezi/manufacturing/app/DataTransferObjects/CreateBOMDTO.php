<?php

namespace Kezi\Manufacturing\DataTransferObjects;

use Kezi\Manufacturing\Enums\BOMType;

readonly class CreateBOMDTO
{
    /**
     * @param  array<int, BOMLineDTO>  $lines
     */
    public function __construct(
        public int $companyId,
        public int $productId,
        public string $code,
        public array $name,
        public BOMType $type,
        public float $quantity,
        public array $lines,
        public bool $isActive = true,
        public ?string $notes = null,
    ) {}
}
