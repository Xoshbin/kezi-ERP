<?php

namespace Xoshbin\FilamentAiHelper\Actions;

use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Xoshbin\FilamentAiHelper\DTOs\FormManipulationResponseDTO;
use Xoshbin\FilamentAiHelper\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class UpdateFormAction
{
    public function __construct(
        private GeminiService $aiService
    ) {}

    public function execute(AIHelperContextDTO $context): FormManipulationResponseDTO
    {
        try {
            // Build enhanced prompt for form updating
            $prompt = $this->buildFormUpdatePrompt($context);

            // Get AI response
            $aiResponse = $this->aiService->generateResponse($prompt);

            // Parse AI response for form data
            $formData = $this->parseFormResponse($aiResponse);

            return new FormManipulationResponseDTO(
                success: true,
                action: 'update_form',
                fields: $formData['fields'] ?? [],
                explanation: $formData['explanation'] ?? 'Form updated successfully',
                warnings: $formData['warnings'] ?? [],
                error: null
            );

        } catch (\Exception $e) {
            Log::error('Form update action failed', [
                'error' => $e->getMessage(),
                'context' => $context->toArray()
            ]);

            return new FormManipulationResponseDTO(
                success: false,
                action: 'update_form',
                fields: [],
                explanation: 'Failed to update form',
                warnings: [],
                error: $e->getMessage()
            );
        }
    }

    private function buildFormUpdatePrompt(AIHelperContextDTO $context): string
    {
        $basePrompt = config('filament-ai-helper.assistant.system_prompt', '');

        $formPrompt = "
FORM UPDATE TASK:
You are helping to update an existing {$context->modelClass} form.

USER REQUEST: {$context->userQuestion}

AVAILABLE FORM FIELDS:
" . $this->formatFormSchema($context->formSchema ?? []) . "

CURRENT FORM VALUES:
" . $this->formatCurrentFormData($context->currentFormData ?? []) . "

EXISTING RECORD DATA:
" . $this->formatRecordData($context->record) . "

CONTEXT INFORMATION:
" . $this->formatContextInfo($context) . "

INSTRUCTIONS:
1. Based on the user's request, determine what form fields should be updated
2. Consider the current values and only change what's necessary
3. Maintain data integrity and business logic consistency
4. Follow accounting best practices and validation rules
5. Be conservative - only change what the user explicitly requests
6. Return ONLY a JSON response in this exact format:

{
    \"action\": \"update_form\",
    \"fields\": {
        \"field_name\": \"new_value\",
        \"another_field\": \"new_value\"
    },
    \"explanation\": \"Brief explanation of what was changed and why\",
    \"warnings\": [\"Any warnings about the changes or potential impacts\"]
}

IMPORTANT:
- Only include fields that should be changed
- Use proper data types (strings, numbers, booleans, dates in Y-m-d format)
- For select fields, use exact option values from the schema
- For money fields, use numeric values without currency symbols
- Consider the impact of changes on related records
- Warn about any potential accounting or business implications
- If the request is unclear, ask for clarification in the explanation
";

        return $basePrompt . "\n\n" . $formPrompt;
    }

    private function formatFormSchema(array $schema): string
    {
        if (empty($schema)) {
            return "No form schema available";
        }

        $formatted = [];
        foreach ($schema as $field => $config) {
            $type = $config['type'] ?? 'text';
            $required = $config['required'] ?? false;
            $options = $config['options'] ?? [];

            $fieldInfo = "- {$field} ({$type})";
            if ($required) {
                $fieldInfo .= " [REQUIRED]";
            }
            if (!empty($options)) {
                $fieldInfo .= " Options: " . implode(', ', array_keys($options));
            }
            if (!empty($config['validation'])) {
                $fieldInfo .= " Validation: " . implode(', ', $config['validation']);
            }

            $formatted[] = $fieldInfo;
        }

        return implode("\n", $formatted);
    }

    private function formatCurrentFormData(array $data): string
    {
        if (empty($data)) {
            return "No current form data available";
        }

        $formatted = [];
        foreach ($data as $field => $value) {
            $displayValue = is_null($value) ? 'null' : (is_array($value) ? json_encode($value) : $value);
            $formatted[] = "- {$field}: {$displayValue}";
        }

        return implode("\n", $formatted);
    }

    private function formatRecordData($record): string
    {
        if (!$record) {
            return "No existing record data";
        }

        $data = $record->toArray();
        $formatted = [];

        foreach ($data as $field => $value) {
            if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) {
                continue; // Skip timestamps
            }

            $displayValue = is_null($value) ? 'null' : (is_array($value) ? json_encode($value) : $value);
            $formatted[] = "- {$field}: {$displayValue}";
        }

        return implode("\n", $formatted);
    }

    private function formatContextInfo(AIHelperContextDTO $context): string
    {
        $info = [];

        if ($context->record) {
            $info[] = "Record Type: " . get_class($context->record);
            $info[] = "Record ID: " . $context->record->getKey();
        }

        if ($context->additionalContext) {
            foreach ($context->additionalContext as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $info[] = "{$key}: {$value}";
                }
            }
        }

        return empty($info) ? "No additional context" : implode("\n", $info);
    }

    private function parseFormResponse(string $response): array
    {
        // Try to extract JSON from the response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Invalid AI response format - no JSON found');
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in AI response: ' . json_last_error_msg());
        }

        // Validate required fields
        if (!isset($decoded['action']) || !isset($decoded['fields'])) {
            throw new \Exception('AI response missing required fields (action, fields)');
        }

        return $decoded;
    }
}
