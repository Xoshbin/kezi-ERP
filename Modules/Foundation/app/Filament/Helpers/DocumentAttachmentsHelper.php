<?php

namespace Modules\Foundation\Filament\Helpers;

use Filament\Forms;
use Filament\Schemas\Components\Section;

/**
 * Helper class for creating standardized document attachment sections in Filament forms.
 */
class DocumentAttachmentsHelper
{
    /**
     * Create a standard document attachments section for Filament forms.
     *
     * @param  string  $directory  Storage directory for attachments (e.g., 'invoices', 'purchase-orders')
     * @param  callable|null  $disabledCallback  Callback to determine if uploads should be disabled
     * @param  callable|null  $deletableCallback  Callback to determine if files can be deleted
     * @param  int  $maxSize  Maximum file size in KB (default: 10240 = 10MB)
     * @param  int  $maxFiles  Maximum number of files (default: 10)
     */
    public static function makeSection(
        string $directory,
        ?callable $disabledCallback = null,
        ?callable $deletableCallback = null,
        int $maxSize = 10240,
        int $maxFiles = 10
    ): Section {
        return Section::make(__('common.attachments'))
            ->description(__('common.attachments_description'))
            ->schema([
                Forms\Components\FileUpload::make('attachments')
                    ->label(__('common.attachments'))
                    ->multiple()
                    ->disk('local')
                    ->directory("document-attachments/{$directory}")
                    ->visibility('private')
                    ->acceptedFileTypes(self::getAcceptedFileTypes())
                    ->maxSize($maxSize)
                    ->maxFiles($maxFiles)
                    ->disabled($disabledCallback ?? fn () => false)
                    ->helperText(__('common.attachments_helper', ['maxSize' => $maxSize / 1024]))
                    ->downloadable()
                    ->openable()
                    ->deletable($deletableCallback ?? fn ($record) => $record === null)
                    ->reorderable()
                    ->saveRelationshipsUsing(function (Forms\Components\FileUpload $component, array $state) {
                        $record = $component->getModelInstance();

                        if (! $record) {
                            return;
                        }

                        // Ensure state is array of paths
                        $paths = array_values($state);

                        // Get existing paths
                        $existing = $record->attachments()->pluck('file_path')->toArray();

                        // Delete removed files
                        $toDelete = array_diff($existing, $paths);
                        if (! empty($toDelete)) {
                            $record->attachments()->whereIn('file_path', $toDelete)->get()->each->delete();
                        }

                        // Create new files
                        $toCreate = array_diff($paths, $existing);
                        foreach ($toCreate as $path) {
                            $record->attachments()->create([
                                'file_path' => $path,
                                // Metadata handled by DocumentAttachment observer if missing
                            ]);
                        }
                    }),
            ])
            ->collapsible()
            ->columnSpanFull()
            ->collapsed(fn ($record) => $record && $record->attachments()->count() === 0);
    }

    /**
     * Get list of accepted file types for document attachments.
     *
     * @return array<int, string>
     */
    private static function getAcceptedFileTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'image/gif',
            'text/plain',
        ];
    }
}
