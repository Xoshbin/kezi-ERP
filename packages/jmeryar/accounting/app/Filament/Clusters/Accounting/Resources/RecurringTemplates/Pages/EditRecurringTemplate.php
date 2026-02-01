<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\RecurringTemplateResource;

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
