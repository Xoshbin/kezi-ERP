<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\ProjectInvoiceResource;

class ListProjectInvoices extends ListRecords
{
    protected static string $resource = ProjectInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
