<?php

namespace Xoshbin\FilamentAiHelper\DTOs;

class AIAssistantResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly string $response,
        public readonly ?string $error = null,
        public readonly array $metadata = []
    ) {
    }
}
