// AccounTech Pro AI Helper JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AI Helper functionality
    initializeAiHelper();
});

function initializeAiHelper() {
    // Auto-scroll chat messages to bottom
    const chatContainers = document.querySelectorAll('.ai-chat-messages');
    chatContainers.forEach(container => {
        const observer = new MutationObserver(() => {
            container.scrollTop = container.scrollHeight;
        });
        
        observer.observe(container, {
            childList: true,
            subtree: true
        });
    });

    // Handle keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter to send message
        if (e.ctrlKey && e.key === 'Enter') {
            const activeTextarea = document.activeElement;
            if (activeTextarea && activeTextarea.matches('.ai-chat-input')) {
                const form = activeTextarea.closest('form');
                if (form) {
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton && !submitButton.disabled) {
                        submitButton.click();
                    }
                }
            }
        }
        
        // Escape to close modal
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.ai-helper-modal[x-show="isOpen"]');
            openModals.forEach(modal => {
                const alpineData = Alpine.$data(modal);
                if (alpineData && alpineData.isOpen) {
                    alpineData.closeModal();
                }
            });
        }
    });

    // Handle focus management for accessibility
    document.addEventListener('click', function(e) {
        if (e.target.matches('.ai-helper-button')) {
            // Focus the textarea when modal opens
            setTimeout(() => {
                const textarea = document.querySelector('.ai-chat-input');
                if (textarea) {
                    textarea.focus();
                }
            }, 300);
        }
    });
}

// Utility functions for AI Helper
window.AiHelper = {
    // Format message content
    formatMessage: function(content) {
        // Convert line breaks to HTML
        return content.replace(/\n/g, '<br>');
    },
    
    // Get current page context for AI
    getCurrentContext: function() {
        const context = {
            modelClass: '',
            modelId: '',
            resourceClass: '',
            url: window.location.href,
            title: document.title
        };
        
        // Try to extract context from Livewire components
        if (window.Livewire) {
            const components = window.Livewire.all();
            components.forEach(component => {
                // Look for record data in component
                if (component.get && typeof component.get === 'function') {
                    try {
                        const record = component.get('record');
                        if (record && record.id) {
                            context.modelId = record.id;
                        }
                    } catch (e) {
                        // Silently continue
                    }
                }
            });
        }
        
        // Try to extract from URL patterns
        const urlParts = window.location.pathname.split('/');
        if (urlParts.length >= 3) {
            // Look for admin/resource/id pattern
            const resourceIndex = urlParts.findIndex(part => part === 'admin');
            if (resourceIndex !== -1 && urlParts[resourceIndex + 2]) {
                const potentialId = urlParts[resourceIndex + 2];
                if (/^\d+$/.test(potentialId)) {
                    context.modelId = potentialId;
                    context.resourceClass = urlParts[resourceIndex + 1];
                }
            }
        }
        
        return context;
    },
    
    // Scroll chat to bottom
    scrollToBottom: function(container) {
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    },
    
    // Handle message sending
    sendMessage: function(wireComponent, message) {
        if (wireComponent && message.trim()) {
            wireComponent.call('sendMessage');
        }
    },
    
    // Clear chat history
    clearChat: function(wireComponent) {
        if (wireComponent) {
            wireComponent.call('clearChat');
        }
    }
};

// Alpine.js components for AI Helper
document.addEventListener('alpine:init', () => {
    Alpine.data('aiHelperModal', () => ({
        isOpen: false,
        
        openModal() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            
            // Focus textarea after modal opens
            this.$nextTick(() => {
                const textarea = this.$el.querySelector('.ai-chat-input');
                if (textarea) {
                    textarea.focus();
                }
            });
        },
        
        closeModal() {
            this.isOpen = false;
            document.body.style.overflow = '';
        }
    }));
    
    Alpine.data('aiChatBox', () => ({
        autoScroll: true,
        
        init() {
            // Watch for new messages and scroll to bottom
            this.$watch('messages', () => {
                if (this.autoScroll) {
                    this.$nextTick(() => {
                        const container = this.$el.querySelector('.ai-chat-messages');
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                }
            });
        },
        
        handleKeydown(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                this.$wire.sendMessage();
            }
        }
    }));
});
