<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use Filament\Resources\Pages\ViewRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add any specific actions for viewing journal entries here if needed
        ];
    }
}
