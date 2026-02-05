<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

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
