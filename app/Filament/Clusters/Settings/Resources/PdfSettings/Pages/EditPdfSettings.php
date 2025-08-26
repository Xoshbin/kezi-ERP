<?php

namespace App\Filament\Clusters\Settings\Resources\PdfSettings\Pages;

use Filament\Actions\Action;
use App\Filament\Clusters\Settings\Resources\PdfSettings\PdfSettingsResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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
                ->url(fn () => route('pdf.preview', ['company' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return __('pdf_settings.edit_title');
    }

    public function getHeading(): string
    {
        return __('pdf_settings.edit_heading', ['company' => $this->record->name]);
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
