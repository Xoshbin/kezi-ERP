<?php

namespace App\Filament\Clusters\Settings\Resources\PdfSettings\Pages;

use App\Filament\Clusters\Settings\Resources\PdfSettings\PdfSettingsResource;
use Filament\Actions;
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
        return __('PDF Settings');
    }

    public function getHeading(): string
    {
        return __('PDF Settings');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add widgets here for PDF preview or statistics
        ];
    }
}
