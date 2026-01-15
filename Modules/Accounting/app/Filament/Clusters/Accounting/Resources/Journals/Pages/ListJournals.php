<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\JournalResource;
use Modules\Foundation\Filament\Actions\DocsAction;

class ListJournals extends ListRecords
{
    use Translatable;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('journal-entries'),
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
