<?php

namespace App\Filament\Resources\JournalResource\Pages;

use App\Filament\Resources\JournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJournals extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
