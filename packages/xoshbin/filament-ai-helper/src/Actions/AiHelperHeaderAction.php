<?php

namespace Xoshbin\FilamentAiHelper\Actions;

use Filament\Actions\Action;

class AiHelperHeaderAction
{
    /**
     * Create the AI Helper header action
     */
    public static function make(): Action
    {
        return Action::make('ai-helper')
            ->label(config('filament-ai-helper.ui.button_label', 'AccounTech Pro'))
            ->icon(config('filament-ai-helper.ui.button_icon', 'heroicon-o-sparkles'))
            ->color('primary')
            ->button()
            ->extraAttributes([
                'class' => 'ai-helper-button bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white shadow-lg',
                'title' => 'Get AI insights for this record',
                'onclick' => 'window.toggleAiChatWidget(); return false;',
            ])
            ->action(function () {
                // Action handled by JavaScript
                return null;
            })
            ->visible(function () {
                // Check if the plugin is enabled
                return config('filament-ai-helper.gemini.api_key') !== null;
            });
    }

    /**
     * Register the action globally for all resource pages
     */
    public static function registerGlobally(): void
    {
        // This method can be used to register the action globally
        // Implementation depends on how Filament handles global actions
    }
}
