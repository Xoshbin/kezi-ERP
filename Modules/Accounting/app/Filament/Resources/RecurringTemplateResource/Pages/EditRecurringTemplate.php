<?php

namespace Modules\Accounting\Filament\Resources\RecurringTemplateResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Accounting\Filament\Resources\RecurringTemplateResource;

class EditRecurringTemplate extends EditRecord
{
    protected static string $resource = RecurringTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
