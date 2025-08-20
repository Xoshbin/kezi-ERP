<?php

namespace AccounTech\FilamentAiHelper\Http\Controllers;

use AccounTech\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use AccounTech\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiChatController extends Controller
{
    public function __construct(
        private readonly GetAIAssistantResponseAction $aiAction
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

            // Create context DTO
            $context = new AIHelperContextDTO(
                modelClass: $validated['model_class'] ?? 'Unknown',
                modelId: $validated['model_id'] ?? 'unknown',
                resourceClass: $validated['resource_class'] ?? 'Unknown',
                userQuestion: $validated['message'],
                locale: app()->getLocale(),
                record: $this->getRecordFromRequest($validated),
                additionalContext: $this->buildAdditionalContext($request)
            );

            // Get AI response
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
}
