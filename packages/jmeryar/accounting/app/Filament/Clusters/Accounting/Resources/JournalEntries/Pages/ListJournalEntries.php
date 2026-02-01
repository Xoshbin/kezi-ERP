<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('journal-entries'),
        ];
    }
}
