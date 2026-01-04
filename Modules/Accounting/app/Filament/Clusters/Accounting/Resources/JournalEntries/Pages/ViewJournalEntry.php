<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use Filament\Resources\Pages\ViewRecord;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;

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
