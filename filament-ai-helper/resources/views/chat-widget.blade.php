{{-- Simple AI Chat Widget - Bottom Right Popup --}}
<style>
.ai-chat-widget-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: system-ui, -apple-system, sans-serif;
}

.ai-chat-widget-button {
    position: relative;
}

.ai-chat-widget-button button {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    border: none;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    color: white;
    font-size: 24px;
}

.ai-chat-widget-button button:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
}

.ai-chat-close-button {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 8px;
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.8);
}

.ai-chat-close-button:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.05);
}

.ai-chat-clear-button {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 8px;
    transition: all 0.2s ease;
    color: rgba(255, 255, 255, 0.8);
}

.ai-chat-clear-button:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    transform: scale(1.05);
}

.ai-chat-widget-popup {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.ai-chat-header {
    background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}

.ai-chat-messages {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    background: #f9fafb;
}

.ai-chat-message {
    margin-bottom: 16px;
}

.ai-chat-input-area {
    padding: 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.ai-typing-indicator {
    display: flex;
    align-items: center;
    space-x: 8px;
    padding: 12px 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    max-width: 280px;
}

.ai-loading-dots {
    display: flex;
    space-x: 4px;
    margin-left: 8px;
}

.ai-loading-dot {
    width: 6px;
    height: 6px;
    background: #6b7280;
    border-radius: 50%;
    animation: ai-loading-bounce 1.4s ease-in-out infinite both;
}

.ai-loading-dot:nth-child(1) { animation-delay: -0.32s; }
.ai-loading-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes ai-loading-bounce {
    0%, 80%, 100% {
        transform: scale(0);
    } 40% {
        transform: scale(1);
    }
}

@media (max-width: 640px) {
    .ai-chat-widget-popup {
        width: 320px;
        height: 450px;
        bottom: 70px;
        right: -10px;
    }
}
</style>

<div x-data="aiChatWidget()" class="ai-chat-widget-container">
    {{-- Chat Widget Button --}}
    <div x-show="!isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="ai-chat-widget-button">
        <button @click="openChat()"
                class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center group">
            <svg class="w-8 h-8 text-white group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </button>
    </div>

    {{-- Chat Widget Popup --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 transform translate-y-4 scale-95"
         class="ai-chat-widget-popup">

        {{-- Chat Header --}}
        <div class="ai-chat-header">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white">AccounTech Pro</h3>
                    <p class="text-sm text-blue-100">AI Accounting Assistant</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button @click="clearChat()"
                        x-show="messages.length > 0"
                        class="ai-chat-clear-button"
                        title="Clear chat history">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>

                <button @click="closeChat()"
                        class="ai-chat-close-button"
                        title="Close chat">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="ai-chat-messages" x-ref="messagesContainer">
            <template x-if="messages.length === 0">
                <div class="flex items-center justify-center h-full py-8">
                    <div class="text-center max-w-xs">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-gray-900 mb-2">Welcome!</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">Hello! I'm AccounTech Pro, your AI accounting assistant. I can help you analyze records, check for potential issues, and provide insights based on accounting best practices. How can I assist you today?</p>
                    </div>
                </div>
            </template>

            <template x-for="message in messages" :key="message.id">
                <div class="flex mb-4 ai-chat-message" :class="message.type === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[280px] px-4 py-3 rounded-lg shadow-sm"
                         :class="message.type === 'user' ? 'bg-blue-600 text-white' : 'bg-white text-gray-900 border border-gray-200'">

                        <template x-if="message.type === 'assistant'">
                            <div class="flex items-center space-x-2 mb-2">
                                <div class="w-5 h-5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-blue-600">AccounTech Pro</span>
                            </div>
                        </template>

                        <div class="text-sm leading-relaxed" x-html="message.content"></div>

                        <div class="text-xs mt-2" :class="message.type === 'user' ? 'text-blue-200' : 'text-gray-500'">
                            <span x-text="formatTime(message.timestamp)"></span>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Loading indicator --}}
            <template x-if="isLoading">
                <div class="flex justify-start mb-4 ai-chat-message">
                    <div class="ai-typing-indicator">
                        <div class="w-5 h-5 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600">Thinking...</span>
                        <div class="ai-loading-dots">
                            <div class="ai-loading-dot"></div>
                            <div class="ai-loading-dot"></div>
                            <div class="ai-loading-dot"></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Chat Input --}}
        <div class="ai-chat-input-area">
            <form @submit.prevent="sendMessage()">
                <div class="mb-3">
                    <textarea x-model="currentMessage"
                              @keydown.ctrl.enter="sendMessage()"
                              :disabled="isLoading"
                              placeholder="Ask me anything about this record..."
                              rows="2"
                              style="width: 100%;"
                              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>

                    <template x-if="errorMessage">
                        <p class="text-sm text-red-600 mt-1" x-text="errorMessage"></p>
                    </template>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500 flex items-center space-x-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Ctrl+Enter</span>
                    </p>

                    <button type="submit"
                            :disabled="isLoading || !currentMessage.trim()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 text-sm font-medium shadow-sm">
                        <span x-text="isLoading ? 'Sending...' : 'Send'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aiChatWidget() {
    return {
        isOpen: false,
        isLoading: false,
        currentMessage: '',
        messages: [],
        errorMessage: '',
        messageId: 1,

        openChat() {
            this.isOpen = true;
            this.$nextTick(() => {
                this.scrollToBottom();
            });
        },

        closeChat() {
            this.isOpen = false;
        },

        clearChat() {
            this.messages = [];
            this.errorMessage = '';
        },

        async sendMessage() {
            if (!this.currentMessage.trim() || this.isLoading) return;

            const userMessage = {
                id: this.messageId++,
                type: 'user',
                content: this.currentMessage,
                timestamp: new Date().toISOString()
            };

            this.messages.push(userMessage);
            const question = this.currentMessage;
            this.currentMessage = '';
            this.isLoading = true;
            this.errorMessage = '';

            this.$nextTick(() => {
                this.scrollToBottom();
            });

            try {
                // Get context from current page
                const context = this.getPageContext();

                // Make API call to AI service
                const response = await fetch('/api/ai-helper/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: question,
                        model_class: context.modelClass,
                        model_id: context.modelId,
                        resource_class: context.resourceClass,
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    const assistantMessage = {
                        id: this.messageId++,
                        type: 'assistant',
                        content: data.response,
                        timestamp: data.timestamp || new Date().toISOString()
                    };

                    this.messages.push(assistantMessage);
                } else {
                    throw new Error(data.error || 'Failed to get AI response');
                }
            } catch (error) {
                console.error('AI Chat error:', error);
                this.errorMessage = error.message || 'Sorry, I encountered an error. Please try again.';
            } finally {
                this.isLoading = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },

        getPageContext() {
            // Extract context from current URL and page
            const path = window.location.pathname;
            let modelClass = null;
            let modelId = null;
            let resourceClass = null;

            // Parse URL to determine context
            if (path.includes('/invoices/')) {
                modelClass = 'App\\Models\\Invoice';
                resourceClass = 'App\\Filament\\Resources\\InvoiceResource';
                const matches = path.match(/\/invoices\/(\d+)/);
                if (matches) modelId = matches[1];
            } else if (path.includes('/vendor-bills/')) {
                modelClass = 'App\\Models\\VendorBill';
                resourceClass = 'App\\Filament\\Resources\\VendorBillResource';
                const matches = path.match(/\/vendor-bills\/(\d+)/);
                if (matches) modelId = matches[1];
            } else if (path.includes('/partners/')) {
                modelClass = 'App\\Models\\Partner';
                resourceClass = 'App\\Filament\\Resources\\PartnerResource';
                const matches = path.match(/\/partners\/(\d+)/);
                if (matches) modelId = matches[1];
            } else if (path.includes('/journal-entries/')) {
                modelClass = 'App\\Models\\JournalEntry';
                resourceClass = 'App\\Filament\\Resources\\JournalEntryResource';
                const matches = path.match(/\/journal-entries\/(\d+)/);
                if (matches) modelId = matches[1];
            }

            return { modelClass, modelId, resourceClass };
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}

// Make the function globally available for the header button
window.toggleAiChatWidget = function() {
    // Find the Alpine component and toggle it
    const widget = document.querySelector('.ai-chat-widget-container');
    if (widget && widget._x_dataStack) {
        const data = widget._x_dataStack[0];
        if (data.isOpen) {
            data.closeChat();
        } else {
            data.openChat();
        }
    }
};
</script>
