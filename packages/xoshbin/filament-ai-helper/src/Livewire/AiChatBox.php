<?php

namespace Xoshbin\FilamentAiHelper\Livewire;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;
use Xoshbin\FilamentAiHelper\Actions\GetAIAssistantResponseAction;
use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;

class AiChatBox extends Component
{
    public string $modelClass = '';

    public string $modelId = '';

    public string $resourceClass = '';

    public string $currentQuestion = '';

    public array $messages = [];

    public bool $isLoading = false;

    public bool $hasError = false;

    public string $errorMessage = '';

    protected $rules = [
        'currentQuestion' => 'required|string|min:3|max:1000',
    ];

    protected $validationMessages = [
        'currentQuestion.required' => 'Please enter a question.',
        'currentQuestion.min' => 'Your question must be at least 3 characters long.',
        'currentQuestion.max' => 'Your question cannot exceed 1000 characters.',
    ];

    protected function messages()
    {
        return $this->validationMessages;
    }

    public function mount(
        string $modelClass = '',
        string $modelId = '',
        string $resourceClass = ''
    ): void {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->resourceClass = $resourceClass;

        $this->initializeChat();
    }

    /**
     * Initialize the chat with a welcome message
     */
    protected function initializeChat(): void
    {
        if (empty($this->modelClass) || empty($this->modelId)) {
            $this->addMessage(
                'assistant',
                "Hello! I'm AccounTech Pro, your AI accounting assistant. ".
                'I can help you with accounting questions and analysis. '.
                'How can I assist you today?'
            );

            return;
        }

        try {
            $context = $this->createContext('');
            $action = app(GetAIAssistantResponseAction::class);
            $welcomeMessage = $action->generateWelcomeMessage($context);

            if (! empty($welcomeMessage)) {
                $this->addMessage('assistant', $welcomeMessage);
            }
        } catch (Exception $e) {
            $this->addMessage(
                'assistant',
                "Hello! I'm AccounTech Pro, your AI accounting assistant. ".
                'How can I help you analyze this record?'
            );
        }
    }

    /**
     * Send a message to the AI assistant
     */
    public function sendMessage(): void
    {
        $this->validate();

        if ($this->isRateLimited()) {
            $this->addError('currentQuestion', 'Too many requests. Please wait a moment before sending another message.');

            return;
        }

        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        // Add user message to chat
        $userMessage = trim($this->currentQuestion);
        $this->addMessage('user', $userMessage);

        try {
            $context = $this->createContext($userMessage);
            $action = app(GetAIAssistantResponseAction::class);
            $response = $action->execute($context);

            $this->addMessage('assistant', $response);

        } catch (Exception $e) {
            $this->hasError = true;
            $this->errorMessage = 'Sorry, I encountered an error while processing your request. Please try again.';
            $this->addMessage('assistant', $this->errorMessage);
        }

        $this->currentQuestion = '';
        $this->isLoading = false;
    }

    /**
     * Clear the chat history
     */
    public function clearChat(): void
    {
        $this->messages = [];
        $this->hasError = false;
        $this->errorMessage = '';
        $this->initializeChat();
    }

    /**
     * Add a message to the chat
     */
    protected function addMessage(string $type, string $content): void
    {
        $this->messages[] = [
            'type' => $type,
            'content' => $content,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Create context DTO for the AI action
     */
    protected function createContext(string $userQuestion): AIHelperContextDTO
    {
        return new AIHelperContextDTO(
            modelClass: $this->modelClass,
            modelId: $this->modelId,
            resourceClass: $this->resourceClass,
            userQuestion: $userQuestion,
            locale: app()->getLocale(),
            record: $this->getRecord()
        );
    }

    /**
     * Get the current record
     */
    protected function getRecord(): ?Model
    {
        if (empty($this->modelClass) || empty($this->modelId)) {
            return null;
        }

        try {
            if (! class_exists($this->modelClass)) {
                return null;
            }

            return $this->modelClass::find($this->modelId);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if the user is rate limited
     */
    protected function isRateLimited(): bool
    {
        if (! config('filament-ai-helper.security.rate_limit.enabled', true)) {
            return false;
        }

        $key = 'ai-helper:'.request()->ip();
        $maxRequests = config('filament-ai-helper.security.rate_limit.max_requests', 10);
        $perMinutes = config('filament-ai-helper.security.rate_limit.per_minutes', 1);

        return RateLimiter::tooManyAttempts($key, $maxRequests);
    }

    /**
     * Handle keyboard shortcuts
     */
    #[On('keydown.ctrl.enter')]
    public function handleCtrlEnter(): void
    {
        if (! empty(trim($this->currentQuestion))) {
            $this->sendMessage();
        }
    }

    /**
     * Get the chat messages for the view
     */
    public function getMessagesProperty(): array
    {
        return $this->messages;
    }

    /**
     * Check if there are any messages
     */
    public function getHasMessagesProperty(): bool
    {
        return count($this->messages) > 0;
    }

    /**
     * Get the current record information for display
     */
    public function getRecordInfoProperty(): array
    {
        $record = $this->getRecord();

        if (! $record) {
            return [
                'type' => 'Unknown',
                'identifier' => 'N/A',
                'exists' => false,
            ];
        }

        // Get model name
        $modelParts = explode('\\', $this->modelClass);
        $modelName = end($modelParts);

        // Get identifier
        $identifierFields = ['number', 'code', 'name', 'title', 'reference'];
        $identifier = "#{$record->getKey()}";

        foreach ($identifierFields as $field) {
            if (isset($record->$field) && ! empty($record->$field)) {
                $identifier = $record->$field;
                break;
            }
        }

        return [
            'type' => $modelName,
            'identifier' => $identifier,
            'exists' => true,
        ];
    }

    public function render()
    {
        return view('filament-ai-helper::livewire.ai-chat-box');
    }
}
