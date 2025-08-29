<div class="flex flex-col h-full bg-white dark:bg-gray-900 rounded-lg ai-helper-modal ai-chat-box">
    {{-- Chat Header --}}
    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-800 rounded-t-lg">
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                <x-filament::icon
                    icon="heroicon-o-sparkles"
                    class="w-5 h-5 text-white"
                />
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    AccounTech Pro AI Assistant
                </h3>
                @if($this->recordInfo['exists'])
                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
                        Analyzing: {{ $this->recordInfo['type'] }} {{ $this->recordInfo['identifier'] }}
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if($this->hasMessages)
                <x-filament::button
                    wire:click="clearChat"
                    size="sm"
                    color="gray"
                    outlined
                    icon="heroicon-o-trash"
                    class="shadow-sm"
                >
                    Clear
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto p-6 space-y-4 max-h-[500px] min-h-[400px] bg-gray-50 dark:bg-gray-900 ai-chat-messages"
         x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
         x-init="scrollToBottom()"
         x-effect="scrollToBottom()">

        @forelse($this->messages as $message)
            <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }} ai-chat-message">
                <div class="max-w-2xl px-4 py-3 rounded-lg shadow-sm {{ $message['type'] === 'user'
                    ? 'bg-blue-600 text-white ai-message-user'
                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 ai-message-assistant' }}">

                    @if($message['type'] === 'assistant')
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="w-6 h-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                <x-filament::icon
                                    icon="heroicon-o-sparkles"
                                    class="w-3 h-3 text-white"
                                />
                            </div>
                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">AccounTech Pro</span>
                        </div>
                        <div class="prose prose-sm dark:prose-invert max-w-none">
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                    @else
                        <div class="text-sm leading-relaxed">
                            {{ $message['content'] }}
                        </div>
                    @endif

                    <div class="text-xs mt-2 {{ $message['type'] === 'user'
                        ? 'text-blue-200'
                        : 'text-gray-500 dark:text-gray-400' }}">
                        {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="flex items-center justify-center h-full">
                <div class="text-center max-w-md">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <x-filament::icon
                            icon="heroicon-o-sparkles"
                            class="w-8 h-8 text-white"
                        />
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Welcome to AccounTech Pro!</h4>
                    <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">{{ $this->getWelcomeMessage() }}</p>
                </div>
            </div>
        @endforelse

        {{-- Loading indicator --}}
        @if($isLoading)
            <div class="flex justify-start ai-chat-message">
                <div class="ai-typing-indicator">
                    <div class="w-6 h-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                        <x-filament::icon
                            icon="heroicon-o-sparkles"
                            class="w-3 h-3 text-white"
                        />
                    </div>
                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">AccounTech Pro is thinking...</span>
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
    <div class="border-t border-gray-200 dark:border-gray-700 p-6 bg-white dark:bg-gray-900 rounded-b-lg">
        <form wire:submit="sendMessage" class="space-y-4">
            <div>
                <x-filament::input.wrapper class="shadow-sm">
                    <x-filament::input
                        wire:model="currentQuestion"
                        placeholder="Ask me anything about this record..."
                        rows="3"
                        type="textarea"
                        :disabled="$isLoading"
                        x-on:keydown.ctrl.enter="$wire.sendMessage()"
                        class="resize-none"
                    />
                </x-filament::input.wrapper>

                @error('currentQuestion')
                    <p class="text-sm text-danger-600 dark:text-danger-400 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Press Ctrl+Enter to send</span>
                </p>

                <x-filament::button
                    type="submit"
                    :disabled="$isLoading || empty(trim($currentQuestion))"
                    icon="heroicon-o-paper-airplane"
                    class="shadow-sm"
                >
                    {{ $isLoading ? 'Thinking...' : 'Send' }}
                </x-filament::button>
            </div>
        </form>

        @if($hasError)
            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-sm">
                <div class="flex items-center space-x-3">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-5 h-5 text-red-600 dark:text-red-400"
                    />
                    <p class="text-sm text-red-700 dark:text-red-300 font-medium">
                        {{ $errorMessage }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
