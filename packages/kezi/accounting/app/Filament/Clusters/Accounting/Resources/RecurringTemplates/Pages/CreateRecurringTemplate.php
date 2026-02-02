<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\RecurringTemplateResource;

class CreateRecurringTemplate extends CreateRecord
{
    protected static string $resource = RecurringTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()?->id;
        $data['created_by_user_id'] = auth()->id();
        $data['next_run_date'] ??= $data['start_date'];

        return $data;
    }
}
