<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\ProjectInvoiceResource;

class ListProjectInvoices extends ListRecords
{
    protected static string $resource = ProjectInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-project-invoicing'),
        ];
    }
}
