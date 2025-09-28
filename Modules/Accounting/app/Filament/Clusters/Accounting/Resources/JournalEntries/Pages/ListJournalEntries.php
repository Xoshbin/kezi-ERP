<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use App\Filament\Actions\DocsAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('opening-balances'),
        ];
    }
}
