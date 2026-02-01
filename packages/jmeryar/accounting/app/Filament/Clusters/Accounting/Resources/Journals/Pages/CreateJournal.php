<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Journals\JournalResource;

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
