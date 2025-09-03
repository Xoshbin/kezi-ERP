<?php

namespace Xoshbin\FilamentAiHelper\Livewire;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
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
        $this->isOpen = ! $this->isOpen;

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
            // Use HTTP client to call the chat endpoint for form manipulation support
            $response = Http::post(route('filament-ai-helper.chat'), [
                'message' => $question,
                'model_class' => $this->modelClass,
                'model_id' => $this->modelId,
                'resource_class' => $this->resourceClass,
                'form_schema' => $this->extractFormSchema(),
                'form_data' => $this->extractCurrentFormData(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success']) {
                    // Check if this is a form manipulation response
                    if (isset($data['action']) && isset($data['fields'])) {
                        // This is a form manipulation response
                        $this->messages[] = [
                            'type' => 'assistant',
                            'content' => $data['response'],
                            'timestamp' => Carbon::now()->toISOString(),
                        ];

                        // Trigger JavaScript form update
                        $this->dispatchBrowserEvent('ai-form-update', [
                            'fields' => $data['fields'],
                            'explanation' => $data['response'],
                            'warnings' => $data['warnings'] ?? [],
                        ]);
                    } else {
                        // Regular chat response
                        $this->messages[] = [
                            'type' => 'assistant',
                            'content' => $data['response'],
                            'timestamp' => Carbon::now()->toISOString(),
                        ];
                    }
                } else {
                    throw new \Exception($data['error'] ?? 'Failed to get AI response');
                }
            } else {
                throw new \Exception('Failed to communicate with AI service');
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
        if (! $this->modelClass || ! $this->modelId) {
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

        if (! $record) {
            return [
                'exists' => false,
                'type' => 'Unknown',
                'identifier' => 'N/A',
            ];
        }

        $type = class_basename($record);
        $identifier = $record->name ?? $record->title ?? $record->reference ?? '#'.$record->id;

        return [
            'exists' => true,
            'type' => $type,
            'identifier' => $identifier,
        ];
    }

    public function getWelcomeMessage(): string
    {
        $recordInfo = $this->getRecordInfoProperty();
        $pageType = $this->detectPageType();

        if ($recordInfo['exists']) {
            $baseMessage = "Hello! I can see you're looking at {$recordInfo['type']} {$recordInfo['identifier']}. I'm AccounTech Pro, your AI accounting assistant.";
        } else {
            $baseMessage = "Hello! I'm AccounTech Pro, your AI accounting assistant.";
        }

        if ($pageType === 'create') {
            return $baseMessage." I can help you fill out this form, analyze data, and provide insights based on accounting best practices. Try asking me to 'create an invoice for customer X' or 'fill the form with default values'. What would you like me to help you with?";
        } elseif ($pageType === 'edit') {
            return $baseMessage." I can help you update this form, analyze the current data, and provide insights based on accounting best practices. Try asking me to 'change the amount to 1000' or 'update the due date'. What would you like me to help you with?";
        }

        return $baseMessage.' I can help you analyze records, check for potential issues, and provide insights based on accounting best practices. How can I assist you today?';
    }

    /**
     * Detect the current page type (create/edit/view)
     */
    private function detectPageType(): ?string
    {
        $url = request()->url();

        if (str_contains($url, '/create')) {
            return 'create';
        }

        if (str_contains($url, '/edit')) {
            return 'edit';
        }

        return null;
    }

    /**
     * Extract form schema from current page (placeholder for now)
     */
    private function extractFormSchema(): ?array
    {
        // This would be enhanced to extract actual form schema from Livewire component
        // For now, return basic schema based on page type
        $pageType = $this->detectPageType();

        if (! $pageType) {
            return null;
        }

        // Basic schema for common fields - this would be dynamically extracted in production
        return [
            'reference' => ['type' => 'text', 'required' => false],
            'date' => ['type' => 'date', 'required' => true],
            'due_date' => ['type' => 'date', 'required' => false],
            'partner_id' => ['type' => 'select', 'required' => true],
            'currency_id' => ['type' => 'select', 'required' => true],
            'notes' => ['type' => 'textarea', 'required' => false],
            'amount' => ['type' => 'number', 'required' => false],
        ];
    }

    /**
     * Extract current form data from page (placeholder for now)
     */
    private function extractCurrentFormData(): ?array
    {
        // This would be enhanced to extract actual form data from Livewire component
        // For now, return empty array - the JavaScript will handle form updates
        return [];
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
