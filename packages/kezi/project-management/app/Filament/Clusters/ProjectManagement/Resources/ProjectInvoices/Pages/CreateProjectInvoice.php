<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Kezi\ProjectManagement\Actions\CreateProjectInvoiceAction;
use Kezi\ProjectManagement\DataTransferObjects\CreateProjectInvoiceDTO;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectInvoices\ProjectInvoiceResource;

/**
 * @extends CreateRecord<\Kezi\ProjectManagement\Models\ProjectInvoice>
 */
class CreateProjectInvoice extends CreateRecord
{
    protected static string $resource = ProjectInvoiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateProjectInvoiceDTO(...[
            'company_id' => (int) $data['company_id'],
            'project_id' => (int) $data['project_id'],
            'period_start' => \Illuminate\Support\Carbon::parse($data['period_start']),
            'period_end' => \Illuminate\Support\Carbon::parse($data['period_end']),
            'include_labor' => (bool) ($data['include_labor'] ?? true),
            'include_expenses' => (bool) ($data['include_expenses'] ?? true),
        ]);

        return app(CreateProjectInvoiceAction::class)->execute($dto);
    }
}
