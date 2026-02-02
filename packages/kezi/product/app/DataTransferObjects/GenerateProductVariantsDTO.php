<?php

namespace Kezi\Product\DataTransferObjects;

readonly class GenerateProductVariantsDTO
{
    public function __construct(
        public int $templateProductId,
        /** @var array<int, array<int>> [attribute_id => [value_id, ...]] */
        public array $attributeValueMap,
        public bool $deleteExisting = false,
    ) {}
}
