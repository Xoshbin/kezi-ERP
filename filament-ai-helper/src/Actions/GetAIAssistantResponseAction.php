<?php

namespace AccounTech\FilamentAiHelper\Actions;

use AccounTech\FilamentAiHelper\DTOs\AIHelperContextDTO;
use AccounTech\FilamentAiHelper\Exceptions\GeminiApiException;
use AccounTech\FilamentAiHelper\Services\GeminiService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GetAIAssistantResponseAction
{
    public function __construct(
        protected GeminiService $geminiService
    ) {
    }

    /**
     * Execute the action and get AI response
     */
    public function execute(AIHelperContextDTO $context): string
    {
        try {
            $prompt = $this->buildPrompt($context);
            
            if (config('filament-ai-helper.security.log_requests', false)) {
                Log::info('AI Assistant request', [
                    'model_class' => $context->modelClass,
                    'model_id' => $context->modelId,
                    'resource_class' => $context->resourceClass,
                    'locale' => $context->locale,
                ]);
            }

            return $this->geminiService->generateResponse($prompt, $context->toArray());

        } catch (GeminiApiException $e) {
            Log::error('AI Assistant API error', [
                'error' => $e->getMessage(),
                'context' => $context->toArray(),
            ]);

            return $this->getFallbackResponse($context);
        } catch (\Exception $e) {
            Log::error('AI Assistant unexpected error', [
                'error' => $e->getMessage(),
                'context' => $context->toArray(),
            ]);

            return $this->getFallbackResponse($context);
        }
    }

    /**
     * Build the complete prompt for the AI
     */
    protected function buildPrompt(AIHelperContextDTO $context): string
    {
        $systemPrompt = $this->getSystemPrompt($context);
        $contextData = $this->getContextData($context);
        $taskInstructions = $this->getTaskInstructions($context);
        $userQuestion = $context->getSanitizedQuestion();

        return implode("\n\n", [
            "SYSTEM INSTRUCTIONS:",
            $systemPrompt,
            "",
            "CONTEXT DATA:",
            $contextData,
            "",
            "TASK INSTRUCTIONS:",
            $taskInstructions,
            "",
            "USER QUESTION:",
            $userQuestion,
            "",
            "Please provide your response in {$context->locale} language."
        ]);
    }

    /**
     * Get the system prompt with locale information
     */
    protected function getSystemPrompt(AIHelperContextDTO $context): string
    {
        $basePrompt = config('filament-ai-helper.assistant.system_prompt');
        
        return $basePrompt . " All responses must be in the {$context->locale} language.";
    }

    /**
     * Get the context data for the current record
     */
    protected function getContextData(AIHelperContextDTO $context): string
    {
        $model = $context->getModel();
        
        if (!$model) {
            return "No record data available.";
        }

        // Load relationships if configured
        $this->loadRelationships($model);

        // Convert model to array and format for AI
        $modelData = $this->formatModelData($model);
        
        // Limit context length if configured
        $maxLength = config('filament-ai-helper.assistant.max_context_length', 8000);
        if (strlen($modelData) > $maxLength) {
            $modelData = substr($modelData, 0, $maxLength) . "\n... (truncated)";
        }

        return "Record Type: {$context->getModelName()}\n" .
               "Record ID: {$context->modelId}\n" .
               "Data:\n" . $modelData;
    }

    /**
     * Load configured relationships for the model
     */
    protected function loadRelationships(Model $model): void
    {
        if (!config('filament-ai-helper.assistant.include_relationships', true)) {
            return;
        }

        $relationships = config('filament-ai-helper.assistant.eager_load_relationships', []);
        
        foreach ($relationships as $relationship) {
            try {
                if (method_exists($model, Str::before($relationship, '.'))) {
                    $model->load($relationship);
                }
            } catch (\Exception $e) {
                // Silently continue if relationship doesn't exist
                continue;
            }
        }
    }

    /**
     * Format model data for AI consumption
     */
    protected function formatModelData(Model $model): string
    {
        $data = $model->toArray();
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get task-specific instructions based on the model type
     */
    protected function getTaskInstructions(AIHelperContextDTO $context): string
    {
        $modelName = strtolower($context->getModelName());
        $contextPrompts = config('filament-ai-helper.assistant.context_prompts', []);

        // Try to find a specific prompt for this model type
        foreach ($contextPrompts as $key => $prompt) {
            if (Str::contains($modelName, $key) || Str::contains($key, $modelName)) {
                return $prompt;
            }
        }

        // Fall back to default prompt
        return $contextPrompts['default'] ?? 
               'Analyze this record and provide insights about its impact on the business.';
    }

    /**
     * Get a fallback response when AI service fails
     */
    protected function getFallbackResponse(AIHelperContextDTO $context): string
    {
        $modelName = $context->getModelName();
        
        return "I apologize, but I'm currently unable to analyze this {$modelName} record due to a technical issue. " .
               "Please try again later or contact support if the problem persists.";
    }

    /**
     * Generate a welcome message for the AI assistant
     */
    public function generateWelcomeMessage(AIHelperContextDTO $context): string
    {
        if (!config('filament-ai-helper.ui.enable_welcome_message', true)) {
            return '';
        }

        $model = $context->getModel();
        $modelName = $context->getModelName();
        
        if (!$model) {
            return "Hello! I'm AccounTech Pro, your AI accounting assistant. How can I help you today?";
        }

        // Try to get a meaningful identifier for the record
        $identifier = $this->getRecordIdentifier($model);
        
        return "Hello! I can see you're looking at {$modelName} {$identifier}. " .
               "I'm AccounTech Pro, your AI accounting assistant. " .
               "I can help you analyze this record, check for potential issues, " .
               "and provide insights based on accounting best practices. " .
               "What would you like to know?";
    }

    /**
     * Get a meaningful identifier for the record
     */
    protected function getRecordIdentifier(Model $model): string
    {
        // Try common identifier fields
        $identifierFields = ['number', 'code', 'name', 'title', 'reference', 'id'];
        
        foreach ($identifierFields as $field) {
            if (isset($model->$field) && !empty($model->$field)) {
                return $model->$field;
            }
        }

        return "#{$model->getKey()}";
    }
}
