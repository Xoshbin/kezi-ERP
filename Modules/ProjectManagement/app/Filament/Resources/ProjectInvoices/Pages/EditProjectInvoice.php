<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\ProjectManagement\Filament\Resources\ProjectInvoices\ProjectInvoiceResource;

class EditProjectInvoice extends EditRecord
{
    protected static string $resource = ProjectInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
