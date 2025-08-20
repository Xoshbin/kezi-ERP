{{-- AI Chat Widget - Bottom Right Popup --}}
<div class="ai-chat-widget-container">
    {{-- Chat Widget Button --}}
    <div class="ai-chat-widget-button"
         x-show="!@this.isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95">
        <button
            wire:click="toggleChat"
            class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
            <x-filament::icon
                icon="heroicon-o-sparkles"
                class="w-8 h-8 text-white group-hover:scale-110 transition-transform duration-300"
            />
        </button>
    </div>

    {{-- Chat Widget Popup --}}
    <div class="ai-chat-widget-popup"
         x-show="@this.isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 transform translate-y-4 scale-95">

        {{-- Chat Header --}}
        <div class="ai-chat-header">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                    <x-filament::icon
                        icon="heroicon-o-sparkles"
                        class="w-5 h-5 text-white"
                    />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">AccounTech Pro</h3>
                    <p class="text-sm text-blue-100">AI Accounting Assistant</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if($this->hasMessages)
                    <button
                        wire:click="clearChat"
                        class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200"
                        title="Clear chat">
                        <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4" />
                    </button>
                @endif

                <button
                    wire:click="closeChat"
                    class="p-2 text-white/70 hover:text-white hover:bg-white/10 rounded-lg transition-colors duration-200"
                    title="Close chat">
                    <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                </button>
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="ai-chat-messages"
             x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
             x-init="scrollToBottom()"
             x-effect="scrollToBottom()">

            @forelse($this->messages as $message)
                <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }} mb-4 ai-chat-message">
                    <div class="max-w-[280px] px-4 py-3 rounded-lg shadow-sm {{ $message['type'] === 'user'
                        ? 'bg-blue-600 text-white ai-message-user'
                        : 'bg-white text-gray-900 border border-gray-200 ai-message-assistant' }}">

                        @if($message['type'] === 'assistant')
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="w-5 h-5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                    <x-filament::icon
                                        icon="heroicon-o-sparkles"
                                        class="w-3 h-3 text-white"
                                    />
                                </div>
                                <span class="text-xs font-semibold text-blue-600">AccounTech Pro</span>
                            </div>
                        @endif

                        <div class="text-sm leading-relaxed {{ $message['type'] === 'assistant' ? 'prose prose-sm max-w-none' : '' }}">
                            @if($message['type'] === 'assistant')
                                {!! nl2br(e($message['content'])) !!}
                            @else
                                {{ $message['content'] }}
                            @endif
                        </div>

                        <div class="text-xs mt-2 {{ $message['type'] === 'user'
                            ? 'text-blue-200'
                            : 'text-gray-500' }}">
                            {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center h-full py-8">
                    <div class="text-center max-w-xs">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <x-filament::icon
                                icon="heroicon-o-sparkles"
                                class="w-6 h-6 text-white"
                            />
                        </div>
                        <h4 class="text-base font-semibold text-gray-900 mb-2">Welcome!</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ $this->getWelcomeMessage() }}</p>
                    </div>
                </div>
            @endforelse

            {{-- Loading indicator --}}
            @if($isLoading)
                <div class="flex justify-start mb-4 ai-chat-message">
                    <div class="ai-typing-indicator">
                        <div class="w-5 h-5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                            <x-filament::icon
                                icon="heroicon-o-sparkles"
                                class="w-3 h-3 text-white"
                            />
                        </div>
                        <span class="text-xs font-semibold text-blue-600">Thinking...</span>
                        <div class="ai-loading-dots">
                            <div class="ai-loading-dot"></div>
                            <div class="ai-loading-dot"></div>
                            <div class="ai-loading-dot"></div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Chat Input --}}
        <div class="ai-chat-input-area">
            <form wire:submit="sendMessage" class="space-y-3">
                <div>
                    <x-filament::input.wrapper class="shadow-sm">
                        <x-filament::input
                            wire:model="currentQuestion"
                            placeholder="Ask me anything about this record..."
                            rows="2"
                            type="textarea"
                            :disabled="$isLoading"
                            x-on:keydown.ctrl.enter="$wire.sendMessage()"
                            class="resize-none text-sm"
                        />
                    </x-filament::input.wrapper>

                    @error('currentQuestion')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 flex items-center space-x-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Ctrl+Enter</span>
                    </p>

                    <x-filament::button
                        type="submit"
                        :disabled="$isLoading || empty(trim($currentQuestion))"
                        icon="heroicon-o-paper-airplane"
                        size="sm"
                        class="shadow-sm"
                    >
                        {{ $isLoading ? 'Sending...' : 'Send' }}
                    </x-filament::button>
                </div>
            </form>

            @if($hasError)
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="w-4 h-4 text-red-600"
                        />
                        <p class="text-sm text-red-700">
                            {{ $errorMessage }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Record Context Info (for debugging) --}}
@if(app()->environment('local'))
    <div class="ai-debug-info" x-show="@this.isOpen">
        <div class="text-xs text-gray-500 p-2 bg-gray-100 rounded">
            @if($this->recordInfo['exists'])
                <strong>Context:</strong> {{ $this->recordInfo['type'] }} {{ $this->recordInfo['identifier'] }}
            @else
                <strong>Context:</strong> No record context
            @endif
        </div>
    </div>
@endif

{{-- Form Manipulation JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form manipulation utilities
    window.AiFormHelper = {
        // Update form field value
        updateField: function(fieldName, value) {
            try {
                // Try different field selectors
                const selectors = [
                    `[wire\\:model="${fieldName}"]`,
                    `[wire\\:model.defer="${fieldName}"]`,
                    `[wire\\:model.lazy="${fieldName}"]`,
                    `[name="${fieldName}"]`,
                    `#${fieldName}`,
                    `[data-field="${fieldName}"]`
                ];

                let field = null;
                for (const selector of selectors) {
                    field = document.querySelector(selector);
                    if (field) break;
                }

                if (!field) {
                    console.warn(`Field not found: ${fieldName}`);
                    return false;
                }

                // Update field based on type
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = Boolean(value);
                } else if (field.tagName === 'SELECT') {
                    field.value = value;
                } else {
                    field.value = value;
                }

                // Trigger change event for Livewire
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));

                // For Livewire components, trigger wire update
                if (field.hasAttribute('wire:model') || field.hasAttribute('wire:model.defer') || field.hasAttribute('wire:model.lazy')) {
                    // Let Livewire handle the update
                    setTimeout(() => {
                        if (window.Livewire) {
                            window.Livewire.emit('refreshComponent');
                        }
                    }, 100);
                }

                return true;
            } catch (error) {
                console.error(`Error updating field ${fieldName}:`, error);
                return false;
            }
        },

        // Update multiple fields
        updateFields: function(fields) {
            const results = {};
            for (const [fieldName, value] of Object.entries(fields)) {
                results[fieldName] = this.updateField(fieldName, value);
            }
            return results;
        },

        // Show form update notification
        showNotification: function(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'warning' ? 'bg-yellow-500 text-white' :
                'bg-red-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                        ×
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }
    };

    // Listen for form manipulation events from AI responses
    document.addEventListener('ai-form-update', function(event) {
        const { fields, explanation, warnings } = event.detail;

        if (fields && Object.keys(fields).length > 0) {
            const results = window.AiFormHelper.updateFields(fields);
            const successCount = Object.values(results).filter(Boolean).length;
            const totalCount = Object.keys(results).length;

            if (successCount === totalCount) {
                window.AiFormHelper.showNotification(
                    `✅ ${explanation || 'Form updated successfully'}`,
                    'success'
                );
            } else {
                window.AiFormHelper.showNotification(
                    `⚠️ Updated ${successCount}/${totalCount} fields. ${explanation || ''}`,
                    'warning'
                );
            }

            // Show warnings if any
            if (warnings && warnings.length > 0) {
                warnings.forEach(warning => {
                    window.AiFormHelper.showNotification(`⚠️ ${warning}`, 'warning');
                });
            }
        }
    });
});
</script>
