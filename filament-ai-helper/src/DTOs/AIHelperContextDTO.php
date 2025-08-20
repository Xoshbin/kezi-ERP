<?php

namespace AccounTech\FilamentAiHelper\DTOs;

use Illuminate\Database\Eloquent\Model;

class AIHelperContextDTO
{
    public function __construct(
        public readonly string $modelClass,
        public readonly int|string $modelId,
        public readonly string $resourceClass,
        public readonly string $userQuestion,
        public readonly string $locale,
        public readonly ?Model $record = null,
        public readonly array $additionalContext = []
    ) {
    }

    /**
     * Create a new instance from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            modelClass: $data['model_class'],
            modelId: $data['model_id'],
            resourceClass: $data['resource_class'],
            userQuestion: $data['user_question'],
            locale: $data['locale'],
            record: $data['record'] ?? null,
            additionalContext: $data['additional_context'] ?? []
        );
    }

    /**
     * Convert the DTO to an array
     */
    public function toArray(): array
    {
        return [
            'model_class' => $this->modelClass,
            'model_id' => $this->modelId,
            'resource_class' => $this->resourceClass,
            'user_question' => $this->userQuestion,
            'locale' => $this->locale,
            'record' => $this->record,
            'additional_context' => $this->additionalContext,
        ];
    }

    /**
     * Get the model instance
     */
    public function getModel(): ?Model
    {
        if ($this->record) {
            return $this->record;
        }

        if (!class_exists($this->modelClass)) {
            return null;
        }

        try {
            return $this->modelClass::find($this->modelId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the resource class name without namespace
     */
    public function getResourceName(): string
    {
        $parts = explode('\\', $this->resourceClass);
        return end($parts);
    }

    /**
     * Get the model class name without namespace
     */
    public function getModelName(): string
    {
        $parts = explode('\\', $this->modelClass);
        return end($parts);
    }

    /**
     * Check if the context has a valid model
     */
    public function hasValidModel(): bool
    {
        return $this->getModel() !== null;
    }

    /**
     * Get sanitized user question
     */
    public function getSanitizedQuestion(): string
    {
        if (!config('filament-ai-helper.security.sanitize_input', true)) {
            return $this->userQuestion;
        }

        // Basic sanitization - remove HTML tags and trim
        return trim(strip_tags($this->userQuestion));
    }
}
