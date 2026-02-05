<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\RecurringTemplateResource;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\RecurringTemplate>
 */
class EditRecurringTemplate extends EditRecord
{
    protected static string $resource = RecurringTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
