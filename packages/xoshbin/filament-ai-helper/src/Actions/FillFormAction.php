<?php

namespace Xoshbin\FilamentAiHelper\Actions;

use Exception;
use Illuminate\Support\Facades\Log;
use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Xoshbin\FilamentAiHelper\DTOs\FormManipulationResponseDTO;
use Xoshbin\FilamentAiHelper\Services\GeminiService;

class FillFormAction
{
    public function __construct(
        private GeminiService $aiService
    ) {}

    public function execute(AIHelperContextDTO $context): FormManipulationResponseDTO
    {
        try {
            // Build enhanced prompt for form filling
            $prompt = $this->buildFormFillPrompt($context);

            // Get AI response
            $aiResponse = $this->aiService->generateResponse($prompt);

            // Parse AI response for form data
            $formData = $this->parseFormResponse($aiResponse);

            return new FormManipulationResponseDTO(
                success: true,
                action: 'fill_form',
                fields: $formData['fields'] ?? [],
                explanation: $formData['explanation'] ?? 'Form filled successfully',
                warnings: $formData['warnings'] ?? [],
                error: null
            );

        } catch (Exception $e) {
            Log::error('Form fill action failed', [
                'error' => $e->getMessage(),
                'context' => $context->toArray(),
            ]);

            return new FormManipulationResponseDTO(
                success: false,
                action: 'fill_form',
                fields: [],
                explanation: 'Failed to fill form',
                warnings: [],
                error: $e->getMessage()
            );
        }
    }

    private function buildFormFillPrompt(AIHelperContextDTO $context): string
    {
        $basePrompt = config('filament-ai-helper.assistant.system_prompt', '');

        $formPrompt = "
FORM FILLING TASK:
You are helping to fill a form for creating a new {$context->modelClass}.

USER REQUEST: {$context->userQuestion}

AVAILABLE FORM FIELDS:
".$this->formatFormSchema($context->formSchema ?? []).'

CURRENT FORM STATE:
'.$this->formatCurrentFormData($context->currentFormData ?? []).'

CONTEXT INFORMATION:
'.$this->formatContextInfo($context)."

INSTRUCTIONS:
1. Based on the user's request, determine what form fields should be filled
2. Use intelligent defaults and business logic for related fields
3. Ensure all required fields are addressed
4. Follow accounting best practices and validation rules
5. Return ONLY a JSON response in this exact format:

{
    \"action\": \"fill_form\",
    \"fields\": {
        \"field_name\": \"value\",
        \"another_field\": \"another_value\"
    },
    \"explanation\": \"Brief explanation of what was filled and why\",
    \"warnings\": [\"Any warnings or considerations for the user\"]
}

IMPORTANT:
- Only include fields that should be changed/filled
- Use proper data types (strings, numbers, booleans, dates in Y-m-d format)
- For select fields, use exact option values from the schema
- For money fields, use numeric values without currency symbols
- Validate against business rules and accounting principles
- If information is missing, ask for clarification in the explanation
";

        return $basePrompt."\n\n".$formPrompt;
    }

    private function formatFormSchema(array $schema): string
    {
        if (empty($schema)) {
            return 'No form schema available';
        }

        $formatted = [];
        foreach ($schema as $field => $config) {
            $type = $config['type'] ?? 'text';
            $required = $config['required'] ?? false;
            $options = $config['options'] ?? [];

            $fieldInfo = "- {$field} ({$type})";
            if ($required) {
                $fieldInfo .= ' [REQUIRED]';
            }
            if (! empty($options)) {
                $fieldInfo .= ' Options: '.implode(', ', array_keys($options));
            }
            if (! empty($config['validation'])) {
                $fieldInfo .= ' Validation: '.implode(', ', $config['validation']);
            }

            $formatted[] = $fieldInfo;
        }

        return implode("\n", $formatted);
    }

    private function formatCurrentFormData(array $data): string
    {
        if (empty($data)) {
            return 'Form is empty (new record)';
        }

        $formatted = [];
        foreach ($data as $field => $value) {
            $formatted[] = "- {$field}: ".(is_null($value) ? 'null' : $value);
        }

        return implode("\n", $formatted);
    }

    private function formatContextInfo(AIHelperContextDTO $context): string
    {
        $info = [];

        if ($context->record) {
            $info[] = 'Related Record: '.get_class($context->record).' ID: '.$context->record->getKey();
        }

        if ($context->additionalContext) {
            foreach ($context->additionalContext as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $info[] = "{$key}: {$value}";
                }
            }
        }

        return empty($info) ? 'No additional context' : implode("\n", $info);
    }

    private function parseFormResponse(string $response): array
    {
        // Try to extract JSON from the response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');

        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception('Invalid AI response format - no JSON found');
        }

        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $decoded = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in AI response: '.json_last_error_msg());
        }

        // Validate required fields
        if (! isset($decoded['action']) || ! isset($decoded['fields'])) {
            throw new Exception('AI response missing required fields (action, fields)');
        }

        return $decoded;
    }
}
