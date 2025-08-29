{{-- Global AI Helper Action for Filament Topbar --}}
@if(config('filament-ai-helper.gemini.api_key'))
    <div x-data="aiHelperGlobal()" class="ai-helper-global">
        <x-filament::button
            @click="openModal()"
            color="primary"
            size="sm"
            icon="heroicon-o-sparkles"
            class="ml-2"
        >
            {{ config('filament-ai-helper.ui.button_label', 'AccounTech Pro') }}
        </x-filament::button>

        {{-- Modal --}}
        <div x-show="isOpen" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal()"></div>
            
            {{-- Modal Content --}}
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     @click.stop>
                    
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            AccounTech Pro AI Assistant
                        </h2>
                        <button @click="closeModal()" 
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                    
                    {{-- Modal Body --}}
                    <div class="flex-1 overflow-hidden">
                        <div x-show="isOpen" x-html="modalContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function aiHelperGlobal() {
            return {
                isOpen: false,
                modalContent: '',
                
                openModal() {
                    this.loadModalContent();
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeModal() {
                    this.isOpen = false;
                    document.body.style.overflow = '';
                },
                
                loadModalContent() {
                    // Get current page context
                    const context = this.getCurrentContext();
                    
                    // Create Livewire component HTML
                    this.modalContent = `
                        <div wire:ignore>
                            @livewire('ai-chat-box', [
                                'modelClass' => '${context.modelClass}',
                                'modelId' => '${context.modelId}',
                                'resourceClass' => '${context.resourceClass}',
                            ])
                        </div>
                    `;
                },
                
                getCurrentContext() {
                    // Try to extract context from current Filament page
                    const context = {
                        modelClass: '',
                        modelId: '',
                        resourceClass: ''
                    };
                    
                    // Look for Livewire components that might have record data
                    const livewireComponents = document.querySelectorAll('[wire\\:id]');
                    
                    livewireComponents.forEach(component => {
                        const wireId = component.getAttribute('wire:id');
                        if (window.Livewire && window.Livewire.find(wireId)) {
                            const livewireComponent = window.Livewire.find(wireId);
                            
                            // Try to get record information
                            if (livewireComponent.get && livewireComponent.get('record')) {
                                const record = livewireComponent.get('record');
                                if (record && record.id) {
                                    context.modelId = record.id;
                                    // Try to determine model class from component name or other hints
                                }
                            }
                        }
                    });
                    
                    return context;
                }
            }
        }
    </script>
@endif
