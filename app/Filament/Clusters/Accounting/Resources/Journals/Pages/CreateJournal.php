<?php

namespace App\Filament\Clusters\Accounting\Resources\Journals\Pages;

use App\Filament\Clusters\Accounting\Resources\Journals\JournalResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateJournal extends CreateRecord
{
    use Translatable;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
