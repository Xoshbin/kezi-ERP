<?php

namespace Modules\Foundation\Filament\Clusters\Settings\Resources\PdfSettings\Pages;

use App\Filament\Clusters\Settings\Resources\PdfSettings\PdfSettingsResource;
use Filament\Resources\Pages\ListRecords;

class ListPdfSettings extends ListRecords
{
    protected static string $resource = PdfSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action since we only edit existing company settings
        ];
    }

    public function getTitle(): string
    {
        return __('pdf_settings.list_title');
    }

    public function getHeading(): string
    {
        return __('pdf_settings.list_title');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add widgets here for PDF preview or statistics
        ];
    }
}
