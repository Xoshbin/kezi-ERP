<?php

namespace Modules\Accounting\Filament\Resources\RecurringTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Resources\RecurringTemplateResource;

class ListRecurringTemplates extends ListRecords
{
    protected static string $resource = RecurringTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
