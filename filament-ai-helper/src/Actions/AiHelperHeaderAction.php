<?php

namespace AccounTech\FilamentAiHelper\Actions;

use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

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
                'title' => 'Get AI insights for this record'
            ])
            ->modalHeading('AccounTech Pro AI Assistant')
            ->modalDescription('Get intelligent insights and analysis for your accounting records')
            ->modalWidth('4xl')
            ->modalAlignment('center')
            ->modalFooterActions([])
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(function (Page $livewire) {
                $record = null;
                $modelClass = '';
                $modelId = '';
                $resourceClass = '';

                // Try to get the current record from the page
                if (method_exists($livewire, 'getRecord')) {
                    $record = $livewire->getRecord();
                }

                if ($record instanceof Model) {
                    $modelClass = get_class($record);
                    $modelId = $record->getKey();
                }

                // Get the resource class
                if (method_exists($livewire, 'getResource')) {
                    $resourceClass = $livewire->getResource();
                } elseif (property_exists($livewire, 'resource')) {
                    $resourceClass = $livewire->resource;
                }

                return view('filament-ai-helper::ai-helper-modal-content', [
                    'modelClass' => $modelClass,
                    'modelId' => $modelId,
                    'resourceClass' => $resourceClass,
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->closeModalByClickingAway(false)
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
