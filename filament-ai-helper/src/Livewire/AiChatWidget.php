<?php

namespace AccounTech\FilamentAiHelper\Livewire;

use AccounTech\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use AccounTech\FilamentAiHelper\DTOs\AIAssistantRequestDTO;
use AccounTech\FilamentAiHelper\DTOs\AIAssistantResponseDTO;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;

class AiChatWidget extends Component
{
    public bool $isOpen = false;
    public bool $isLoading = false;
    public bool $hasError = false;
    public string $errorMessage = '';
    public string $currentQuestion = '';
    public array $messages = [];

    // Record context
    public ?string $modelClass = null;
    public ?string $modelId = null;
    public ?string $resourceClass = null;

    protected $rules = [
        'currentQuestion' => 'required|string|min:3|max:1000',
    ];

    protected $messages_validation = [
        'currentQuestion.required' => 'Please enter a question.',
        'currentQuestion.min' => 'Your question must be at least 3 characters.',
        'currentQuestion.max' => 'Your question cannot exceed 1000 characters.',
    ];

    public function mount(): void
    {
        $this->loadRecordContext();
    }

    #[On('ai-chat-toggle')]
    public function toggleChat(): void
    {
        $this->isOpen = !$this->isOpen;

        if ($this->isOpen) {
            $this->loadRecordContext();
        }
    }

    #[On('ai-chat-open')]
    public function openChat(): void
    {
        $this->isOpen = true;
        $this->loadRecordContext();
    }

    #[On('ai-chat-close')]
    public function closeChat(): void
    {
        $this->isOpen = false;
    }

    public function sendMessage(): void
    {
        $this->validate();

        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        // Add user message
        $this->messages[] = [
            'type' => 'user',
            'content' => $this->currentQuestion,
            'timestamp' => Carbon::now()->toISOString(),
        ];

        $question = $this->currentQuestion;
        $this->currentQuestion = '';

        try {
            $record = $this->getCurrentRecord();

            $request = new AIAssistantRequestDTO(
                question: $question,
                modelClass: $this->modelClass,
                modelId: $this->modelId,
                resourceClass: $this->resourceClass,
                record: $record
            );

            $action = app(GetAIAssistantResponseAction::class);
            $response = $action->execute($request);

            if ($response instanceof AIAssistantResponseDTO && $response->success) {
                $this->messages[] = [
                    'type' => 'assistant',
                    'content' => $response->response,
                    'timestamp' => Carbon::now()->toISOString(),
                ];
            } else {
                throw new \Exception($response->error ?? 'Failed to get AI response');
            }
        } catch (\Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Sorry, I encountered an error. Please try again.';

            // Remove the user message if AI failed
            array_pop($this->messages);
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->hasError = false;
        $this->errorMessage = '';
    }

    protected function loadRecordContext(): void
    {
        // Try to get context from current page
        $request = request();
        $route = $request->route();

        if ($route) {
            $parameters = $route->parameters();

            // Look for tenant and record ID in route parameters
            if (isset($parameters['tenant']) && isset($parameters['record'])) {
                $this->modelId = $parameters['record'];
            } elseif (isset($parameters['record'])) {
                $this->modelId = $parameters['record'];
            }

            // Use configurable context mapping for model detection
            $path = $request->path();
            $contextMapping = config('filament-ai-helper.assistant.context_mapping', []);

            foreach ($contextMapping as $urlSegment => $mapping) {
                if (str_contains($path, "/{$urlSegment}/")) {
                    $this->modelClass = $mapping['model'];
                    $this->resourceClass = $mapping['resource'];
                    break;
                }
            }
        }
    }

    protected function getCurrentRecord(): ?Model
    {
        if (!$this->modelClass || !$this->modelId) {
            return null;
        }

        try {
            if (class_exists($this->modelClass)) {
                return $this->modelClass::find($this->modelId);
            }
        } catch (\Exception $e) {
            // Silently fail if record not found
        }

        return null;
    }

    public function getRecordInfoProperty(): array
    {
        $record = $this->getCurrentRecord();

        if (!$record) {
            return [
                'exists' => false,
                'type' => 'Unknown',
                'identifier' => 'N/A',
            ];
        }

        $type = class_basename($record);
        $identifier = $record->name ?? $record->title ?? $record->reference ?? '#' . $record->id;

        return [
            'exists' => true,
            'type' => $type,
            'identifier' => $identifier,
        ];
    }

    public function getWelcomeMessage(): string
    {
        $recordInfo = $this->getRecordInfoProperty();

        if ($recordInfo['exists']) {
            return "Hello! I can see you're looking at {$recordInfo['type']} {$recordInfo['identifier']}. I'm AccounTech Pro, your AI accounting assistant. I can help you analyze this record, check for potential issues, and provide insights based on accounting best practices. What would you like to know?";
        }

        return "Hello! I'm AccounTech Pro, your AI accounting assistant. I can help you analyze records, check for potential issues, and provide insights based on accounting best practices. How can I assist you today?";
    }

    public function getHasMessagesProperty(): bool
    {
        return count($this->messages) > 0;
    }

    public function render()
    {
        return view('filament-ai-helper::livewire.ai-chat-widget');
    }
}
