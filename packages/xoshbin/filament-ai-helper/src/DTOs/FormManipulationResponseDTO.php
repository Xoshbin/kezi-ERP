<?php

namespace Xoshbin\FilamentAiHelper\DTOs;

class FormManipulationResponseDTO
{
    public function __construct(
        public bool $success,
        public string $action,
        public array $fields,
        public string $explanation,
        public array $warnings = [],
        public ?string $error = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'action' => $this->action,
            'fields' => $this->fields,
            'explanation' => $this->explanation,
            'warnings' => $this->warnings,
            'error' => $this->error,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            action: $data['action'] ?? '',
            fields: $data['fields'] ?? [],
            explanation: $data['explanation'] ?? '',
            warnings: $data['warnings'] ?? [],
            error: $data['error'] ?? null
        );
    }
}
