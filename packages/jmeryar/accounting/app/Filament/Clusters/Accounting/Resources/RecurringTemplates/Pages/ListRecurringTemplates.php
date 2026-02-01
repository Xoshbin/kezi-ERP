<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\RecurringTemplates\RecurringTemplateResource;

class ListRecurringTemplates extends ListRecords
{
    protected static string $resource = RecurringTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('recurring-templates'),
            Actions\CreateAction::make(),
        ];
    }
}
