<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Journals\Pages;

use App\Filament\Clusters\Accounting\Resources\Journals\JournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditJournal extends EditRecord
{
    use Translatable;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
