<?php

namespace Xoshbin\FilamentAiHelper\Concerns;

use Filament\Actions\Action;
use Xoshbin\FilamentAiHelper\Actions\AiHelperHeaderAction;

trait HasAiHelper
{
    /**
     * Get the AI Helper action for header actions
     */
    protected function getAiHelperAction(): Action
    {
        return AiHelperHeaderAction::make();
    }

    /**
     * Add AI Helper to existing header actions
     */
    protected function addAiHelperToHeaderActions(array $actions = []): array
    {
        if (! $this->shouldShowAiHelper()) {
            return $actions;
        }

        // Add the AI Helper action at the beginning to make it more prominent
        return array_merge([$this->getAiHelperAction()], $actions);
    }

    /**
     * Determine if the AI Helper should be shown
     */
    protected function shouldShowAiHelper(): bool
    {
        // Respect global enable/disable config
        if (! config('filament-ai-helper.enabled', true)) {
            return false;
        }

        // Check if API key is configured
        if (empty(config('filament-ai-helper.gemini.api_key'))) {
            return false;
        }

        // Check if the current user has permission (override in your pages if needed)
        return $this->canUseAiHelper();
    }

    /**
     * Check if the current user can use the AI Helper
     * Override this method in your pages to implement custom authorization
     */
    protected function canUseAiHelper(): bool
    {
        return true;
    }
}
