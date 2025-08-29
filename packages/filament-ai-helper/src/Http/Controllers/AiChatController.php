<?php

namespace AccounTech\FilamentAiHelper\Http\Controllers;

use AccounTech\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use AccounTech\FilamentAiHelper\Actions\FillFormAction;
use AccounTech\FilamentAiHelper\Actions\UpdateFormAction;
use AccounTech\FilamentAiHelper\DTOs\AIHelperContextDTO;
use AccounTech\FilamentAiHelper\Services\FormSchemaExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiChatController extends Controller
{
    public function __construct(
        private readonly GetAIAssistantResponseAction $aiAction,
        private readonly FillFormAction $fillFormAction,
        private readonly UpdateFormAction $updateFormAction,
        private readonly FormSchemaExtractor $formSchemaExtractor
    ) {
    }

    /**
     * Handle chat message and return AI response
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|min:1|max:1000',
                'model_class' => 'nullable|string',
                'model_id' => 'nullable|string',
                'resource_class' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid input: ' . $validator->errors()->first(),
                ], 422);
            }

            $validated = $validator->validated();

            // Extract form information if available
            $pageType = $this->formSchemaExtractor->detectPageType($request);
            $formSchema = $this->extractFormSchema($request);
            $currentFormData = $this->extractCurrentFormData($request);

            // Create context DTO
            $context = new AIHelperContextDTO(
                modelClass: $validated['model_class'] ?? 'Unknown',
                modelId: $validated['model_id'] ?? 'unknown',
                resourceClass: $validated['resource_class'] ?? 'Unknown',
                userQuestion: $validated['message'],
                locale: app()->getLocale(),
                record: $this->getRecordFromRequest($validated),
                additionalContext: $this->buildAdditionalContext($request),
                formSchema: $formSchema,
                currentFormData: $currentFormData,
                pageType: $pageType
            );

            // Check if this is a form manipulation request
            if ($context->isFormPage() && $this->isFormManipulationRequest($validated['message'])) {
                return $this->handleFormManipulation($context);
            }

            // Get AI response for regular chat
            $result = $this->aiAction->execute($context);

            // Handle both string and object responses for backward compatibility
            if (is_string($result)) {
                $response = $result;
                $success = true;
            } elseif (is_object($result)) {
                $response = $result->response ?? $result->answer ?? 'No response available';
                $success = $result->success ?? true;
            } else {
                $response = 'Invalid response format';
                $success = false;
            }

            return response()->json([
                'success' => $success,
                'response' => $response,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('AI Chat error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'I apologize, but I encountered an error while processing your request. Please try again in a moment.',
            ], 500);
        }
    }

    /**
     * Get the record from the request context
     */
    private function getRecordFromRequest(array $validated): ?\Illuminate\Database\Eloquent\Model
    {
        if (empty($validated['model_class']) || empty($validated['model_id'])) {
            return null;
        }

        try {
            $modelClass = $validated['model_class'];

            if (!class_exists($modelClass)) {
                return null;
            }

            return $modelClass::find($validated['model_id']);
        } catch (\Exception $e) {
            Log::warning('Failed to load record for AI context', [
                'model_class' => $validated['model_class'] ?? 'unknown',
                'model_id' => $validated['model_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build additional context from the request
     */
    private function buildAdditionalContext(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'url' => $request->url(),
            'referer' => $request->header('referer'),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Extract form schema from request
     */
    private function extractFormSchema(Request $request): ?array
    {
        try {
            // Try to get form schema from request data
            if ($request->has('form_schema')) {
                return $request->get('form_schema');
            }

            // For now, return null - this would be enhanced to extract from Livewire component
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to extract form schema', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Extract current form data from request
     */
    private function extractCurrentFormData(Request $request): ?array
    {
        try {
            // Try to get current form data from request
            if ($request->has('form_data')) {
                return $request->get('form_data');
            }

            // For now, return null - this would be enhanced to extract from Livewire component
            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to extract current form data', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if the message is a form manipulation request
     */
    private function isFormManipulationRequest(string $message): bool
    {
        $message = strtolower($message);

        // Question patterns that should NOT be treated as form manipulation
        $questionPatterns = [
            'what is',
            'what are',
            'how is',
            'how are',
            'why is',
            'why are',
            'when is',
            'when are',
            'where is',
            'where are',
            'who is',
            'who are',
            'which is',
            'which are',
            'can you explain',
            'tell me about',
            'show me',
            'analyze',
            'calculate',
            'help me understand'
        ];

        // If it's a question, don't treat as form manipulation
        foreach ($questionPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return false;
            }
        }

        // Action keywords that indicate form manipulation
        $actionKeywords = [
            'fill', 'create', 'add', 'set', 'update', 'change', 'edit', 'modify',
            'enter', 'input', 'save', 'submit'
        ];

        foreach ($actionKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle form manipulation requests
     */
    private function handleFormManipulation(AIHelperContextDTO $context): JsonResponse
    {
        try {
            if ($context->isCreatePage()) {
                $result = $this->fillFormAction->execute($context);
            } elseif ($context->isEditPage()) {
                $result = $this->updateFormAction->execute($context);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Form manipulation not supported on this page type',
                ], 400);
            }

            return response()->json([
                'success' => $result->success,
                'action' => $result->action,
                'fields' => $result->fields,
                'response' => $result->explanation,
                'warnings' => $result->warnings,
                'error' => $result->error,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Form manipulation failed', [
                'error' => $e->getMessage(),
                'context' => $context->toArray()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Form manipulation failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
