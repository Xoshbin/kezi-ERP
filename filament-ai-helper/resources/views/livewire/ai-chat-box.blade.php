<div class="ai-chat-box">
    {{-- Chat Header --}}
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <x-filament::icon 
                    icon="heroicon-o-sparkles" 
                    class="w-6 h-6 text-primary-600 dark:text-primary-400"
                />
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    AccounTech Pro AI Assistant
                </h3>
                @if($this->recordInfo['exists'])
                    <p class="text-sm text-gray-500 dark:text-gray-400">
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
                >
                    Clear
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- Chat Messages --}}
    <div class="flex-1 overflow-y-auto p-4 space-y-4 max-h-96 min-h-[300px]" 
         x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
         x-init="scrollToBottom()"
         x-effect="scrollToBottom()">
        
        @forelse($this->messages as $message)
            <div class="flex {{ $message['type'] === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg {{ $message['type'] === 'user' 
                    ? 'bg-primary-600 text-white' 
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' }}">
                    
                    @if($message['type'] === 'assistant')
                        <div class="flex items-start space-x-2">
                            <x-filament::icon 
                                icon="heroicon-o-cpu-chip" 
                                class="w-4 h-4 mt-0.5 text-primary-600 dark:text-primary-400 flex-shrink-0"
                            />
                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! nl2br(e($message['content'])) !!}
                            </div>
                        </div>
                    @else
                        <div class="text-sm">
                            {{ $message['content'] }}
                        </div>
                    @endif
                    
                    <div class="text-xs opacity-70 mt-1">
                        {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                <x-filament::icon 
                    icon="heroicon-o-chat-bubble-left-right" 
                    class="w-12 h-12 mx-auto mb-4 opacity-50"
                />
                <p>Start a conversation with your AI assistant</p>
            </div>
        @endforelse

        {{-- Loading indicator --}}
        @if($isLoading)
            <div class="flex justify-start">
                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800">
                    <div class="flex items-center space-x-2">
                        <x-filament::icon 
                            icon="heroicon-o-cpu-chip" 
                            class="w-4 h-4 text-primary-600 dark:text-primary-400"
                        />
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Chat Input --}}
    <div class="border-t border-gray-200 dark:border-gray-700 p-4">
        <form wire:submit="sendMessage" class="space-y-3">
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input
                        wire:model="currentQuestion"
                        placeholder="Ask me anything about this record..."
                        rows="3"
                        type="textarea"
                        :disabled="$isLoading"
                        x-on:keydown.ctrl.enter="$wire.sendMessage()"
                    />
                </x-filament::input.wrapper>
                
                @error('currentQuestion')
                    <p class="text-sm text-danger-600 dark:text-danger-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Press Ctrl+Enter to send
                </p>
                
                <x-filament::button
                    type="submit"
                    :disabled="$isLoading || empty(trim($currentQuestion))"
                    icon="heroicon-o-paper-airplane"
                >
                    {{ $isLoading ? 'Thinking...' : 'Send' }}
                </x-filament::button>
            </div>
        </form>

        @if($hasError)
            <div class="mt-3 p-3 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg">
                <div class="flex items-center space-x-2">
                    <x-filament::icon 
                        icon="heroicon-o-exclamation-triangle" 
                        class="w-4 h-4 text-danger-600 dark:text-danger-400"
                    />
                    <p class="text-sm text-danger-600 dark:text-danger-400">
                        {{ $errorMessage }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.ai-chat-box {
    @apply flex flex-col h-full;
}
</style>
