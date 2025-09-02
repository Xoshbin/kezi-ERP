<?php

namespace Xoshbin\FilamentAiHelper\Actions;

use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Xoshbin\FilamentAiHelper\DTOs\AIAssistantRequestDTO;
use Xoshbin\FilamentAiHelper\DTOs\AIAssistantResponseDTO;
use Xoshbin\FilamentAiHelper\Exceptions\GeminiApiException;
use Xoshbin\FilamentAiHelper\Services\GeminiService;
use Xoshbin\FilamentAiHelper\Services\DeepContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GetAIAssistantResponseAction
{
    public function __construct(
        protected GeminiService $geminiService,
        private readonly DeepContextService $deepContextService
    ) {
    }

    /**
     * Execute the action and get AI response
     */
    public function execute(AIHelperContextDTO|AIAssistantRequestDTO $context): string
    {
        // Handle both DTO types for backward compatibility
        if ($context instanceof AIAssistantRequestDTO) {
            $result = $this->executeFromRequest($context);
            // Return just the response string for consistency
            return $result->response ?? 'No response available';
        }

        return $this->executeFromContext($context);
    }

    protected function executeFromRequest(AIAssistantRequestDTO $request): AIAssistantResponseDTO
    {
        try {
            // Convert request to context DTO
            $context = new AIHelperContextDTO(
                modelClass: $request->modelClass ?? '',
                modelId: $request->modelId ?? '',
                resourceClass: $request->resourceClass ?? '',
                userQuestion: $request->question,
                locale: app()->getLocale(),
                additionalContext: $request->additionalContext
            );

            $result = $this->executeFromContext($context);

            return new AIAssistantResponseDTO(
                success: true,
                response: $result->response ?? $result->answer ?? 'No response available'
            );
        } catch (\Exception $e) {
            Log::error('AI Assistant Request failed', [
                'error' => $e->getMessage(),
                'question' => $request->question,
            ]);

            return new AIAssistantResponseDTO(
                success: false,
                response: '',
                error: 'Sorry, I encountered an error. Please try again.'
            );
        }
    }

    protected function executeFromContext(AIHelperContextDTO $context): string
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

            $response = $this->geminiService->generateResponse($prompt, $context->toArray());

            return $response;

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

        // Get deep context using the specialized service
        $deepContext = $this->deepContextService->buildDeepContext($model);

        // Format the deep context for AI consumption
        $contextData = $this->formatDeepContextForAI($deepContext, $context);

        // Limit context length if configured
        $maxLength = config('filament-ai-helper.assistant.max_context_length', 15000); // Increased for deep context
        if (strlen($contextData) > $maxLength) {
            $contextData = substr($contextData, 0, $maxLength) . "\n... (context truncated due to length)";
        }

        return $contextData;
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
            } catch (\Exception) {
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
     * Format deep context data for AI consumption
     */
    protected function formatDeepContextForAI(array $deepContext, AIHelperContextDTO $context): string
    {
        $output = [];

        $output[] = "=== COMPREHENSIVE RECORD ANALYSIS ===";
        $output[] = "Record Type: {$context->getModelName()}";
        $output[] = "Record ID: {$context->modelId}";
        $output[] = "";

        // Basic Information
        if (isset($deepContext['basic_info'])) {
            $output[] = "BASIC INFORMATION:";
            foreach ($deepContext['basic_info'] as $key => $value) {
                $output[] = "  {$key}: {$value}";
            }
            $output[] = "";
        }

        // Relationship Context
        if (isset($deepContext['relationships'])) {
            $output[] = "RELATIONSHIP CONTEXT:";
            $output[] = $this->formatRelationshipContext($deepContext['relationships']);
            $output[] = "";
        }

        // Historical Data
        if (isset($deepContext['historical_data']) && !empty($deepContext['historical_data'])) {
            $output[] = "HISTORICAL DATA:";
            $output[] = json_encode($deepContext['historical_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output[] = "";
        }

        // Financial Metrics
        if (isset($deepContext['financial_metrics']) && !empty($deepContext['financial_metrics'])) {
            $output[] = "FINANCIAL METRICS:";
            $output[] = json_encode($deepContext['financial_metrics'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output[] = "";
        }

        // Risk Indicators
        if (isset($deepContext['risk_indicators']) && !empty($deepContext['risk_indicators'])) {
            $output[] = "RISK INDICATORS:";
            $output[] = json_encode($deepContext['risk_indicators'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output[] = "";
        }

        // Business Insights
        if (isset($deepContext['business_insights']) && !empty($deepContext['business_insights'])) {
            $output[] = "BUSINESS INSIGHTS:";
            $output[] = json_encode($deepContext['business_insights'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output[] = "";
        }

        return implode("\n", $output);
    }

    /**
     * Format relationship context in a readable way
     */
    protected function formatRelationshipContext(array $relationships): string
    {
        $output = [];

        foreach ($relationships as $relationshipType => $data) {
            $output[] = "  {$relationshipType}:";

            if (is_array($data)) {
                $output[] = "    " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $output[] = "    {$data}";
            }
        }

        return implode("\n", $output);
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
        $fallbackMessage = "I apologize, but I'm currently unable to analyze this {$modelName} record due to a technical issue. " .
                          "Please try again later or contact support if the problem persists.";

        return $fallbackMessage;
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
