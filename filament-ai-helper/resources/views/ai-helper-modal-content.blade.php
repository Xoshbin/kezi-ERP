<div class="ai-helper-modal-content">
    @livewire('ai-chat-box', [
        'modelClass' => $modelClass,
        'modelId' => $modelId,
        'resourceClass' => $resourceClass,
    ])
</div>

<style>
.ai-helper-modal-content {
    min-height: 500px;
    max-height: 80vh;
}
</style>
