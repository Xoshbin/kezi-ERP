<?php

namespace Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Modules\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Modules\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\ProjectInvoiceResource;

class CreateProjectInvoice extends CreateRecord
{
    protected static string $resource = ProjectInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateProjectInvoiceDTO(
            project_id: $data['project_id'],
            period_start: \Carbon\Carbon::parse($data['period_start']),
            period_end: \Carbon\Carbon::parse($data['period_end']),
            include_labor: $data['include_labor'] ?? true,
            include_expenses: $data['include_expenses'] ?? true,
        );

        return app(CreateProjectInvoiceAction::class)->execute($dto);
    }
}
