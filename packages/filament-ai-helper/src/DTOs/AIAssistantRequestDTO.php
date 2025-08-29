<?php

namespace AccounTech\FilamentAiHelper\DTOs;

class AIAssistantRequestDTO
{
    public function __construct(
        public readonly string $question,
        public readonly ?string $modelClass = null,
        public readonly ?string $modelId = null,
        public readonly ?string $resourceClass = null,
        public readonly array $additionalContext = []
    ) {}
}
