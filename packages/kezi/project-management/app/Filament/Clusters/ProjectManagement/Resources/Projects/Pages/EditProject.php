<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Kezi\ProjectManagement\Actions\UpdateProjectAction;
use Kezi\ProjectManagement\DataTransferObjects\UpdateProjectDTO;
use Kezi\ProjectManagement\Enums\BillingType;
use Kezi\ProjectManagement\Enums\ProjectStatus;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Projects\ProjectResource;

/**
 * @extends EditRecord<\Kezi\ProjectManagement\Models\Project>
 */
class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\Action::make('activate')
                ->action(fn (Model $record) => $record->update(['status' => ProjectStatus::Active]))
                ->visible(fn (Model $record) => $record->status === ProjectStatus::Draft)
                ->color('success')
                ->requiresConfirmation(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $dto = new UpdateProjectDTO(
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            manager_id: $data['manager_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            status: $data['status'] instanceof ProjectStatus ? $data['status'] : ProjectStatus::from($data['status']),
            start_date: isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            end_date: isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
            budget_amount: (string) ($data['budget_amount'] ?? '0'),
            billing_type: $data['billing_type'] instanceof BillingType ? $data['billing_type'] : BillingType::from($data['billing_type']),
            is_billable: (bool) $data['is_billable'],
        );

        return app(UpdateProjectAction::class)->execute($record, $dto);
    }
}
