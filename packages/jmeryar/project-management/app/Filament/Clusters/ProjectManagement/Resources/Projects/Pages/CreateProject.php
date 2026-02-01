<?php

namespace Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Jmeryar\ProjectManagement\Actions\CreateProjectAction;
use Jmeryar\ProjectManagement\DataTransferObjects\CreateProjectDTO;
use Jmeryar\ProjectManagement\Enums\BillingType;
use Jmeryar\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\ProjectResource;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $dto = new CreateProjectDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            manager_id: $data['manager_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            start_date: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            end_date: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            budget_amount: (string) ($data['budget_amount'] ?? '0'),
            billing_type: $data['billing_type'] instanceof BillingType ? $data['billing_type'] : BillingType::from($data['billing_type']),
            is_billable: (bool) $data['is_billable'],
        );

        return app(CreateProjectAction::class)->execute($dto);
    }
}
