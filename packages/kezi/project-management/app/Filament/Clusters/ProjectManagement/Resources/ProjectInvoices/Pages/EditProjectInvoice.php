<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages;

use \Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\ProjectInvoiceResource;

/**
 * @extends EditRecord<\Kezi\ProjectManagement\Models\ProjectInvoice>
 */
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
