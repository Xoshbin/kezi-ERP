<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
