<?php

namespace Modules\Foundation\Filament\Resources\PdfSettings\Pages;

use App\Models\Company;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Modules\Foundation\Filament\Resources\PdfSettings\PdfSettingsResource;

class EditPdfSettings extends EditRecord
{
    protected static string $resource = PdfSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview_pdf')
                ->label(__('pdf_settings.preview_pdf'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(function (): string {
                    $record = $this->getRecord();
                    $id = $record instanceof Company ? (int) $record->getKey() : 0;

                    return route('pdf.preview', ['company' => $id]);
                })
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return __('pdf_settings.edit_title');
    }

    public function getHeading(): string
    {
        $record = $this->getRecord();
        $name = $record instanceof Company ? (string) $record->name : '';

        return __('pdf_settings.edit_heading', ['company' => $name]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('pdf_settings.settings_saved'))
            ->body(__('pdf_settings.settings_saved_body'));
    }
}
