<?php

namespace Xoshbin\FilamentAiHelper\DTOs;

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
        public readonly array $additionalContext = [],
        public readonly ?array $formSchema = null,
        public readonly ?array $currentFormData = null,
        public readonly ?string $pageType = null
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
            additionalContext: $data['additional_context'] ?? [],
            formSchema: $data['form_schema'] ?? null,
            currentFormData: $data['current_form_data'] ?? null,
            pageType: $data['page_type'] ?? null
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
            'form_schema' => $this->formSchema,
            'current_form_data' => $this->currentFormData,
            'page_type' => $this->pageType,
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

    /**
     * Check if this context is for a form page (create/edit)
     */
    public function isFormPage(): bool
    {
        return in_array($this->pageType, ['create', 'edit']);
    }

    /**
     * Check if this is a create page
     */
    public function isCreatePage(): bool
    {
        return $this->pageType === 'create';
    }

    /**
     * Check if this is an edit page
     */
    public function isEditPage(): bool
    {
        return $this->pageType === 'edit';
    }

    /**
     * Check if form schema is available
     */
    public function hasFormSchema(): bool
    {
        return !empty($this->formSchema);
    }

    /**
     * Check if current form data is available
     */
    public function hasCurrentFormData(): bool
    {
        return !empty($this->currentFormData);
    }

    /**
     * Get required form fields
     */
    public function getRequiredFields(): array
    {
        if (!$this->hasFormSchema()) {
            return [];
        }

        return array_keys(array_filter($this->formSchema, function ($field) {
            return $field['required'] ?? false;
        }));
    }

    /**
     * Get form field types
     */
    public function getFieldTypes(): array
    {
        if (!$this->hasFormSchema()) {
            return [];
        }

        $types = [];
        foreach ($this->formSchema as $field => $config) {
            $types[$field] = $config['type'] ?? 'text';
        }

        return $types;
    }
}
