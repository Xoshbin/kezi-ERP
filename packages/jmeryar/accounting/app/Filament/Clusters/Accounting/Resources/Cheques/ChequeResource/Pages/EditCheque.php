<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequeResource;

class EditCheque extends EditRecord
{
    protected static string $resource = ChequeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // We might want to disable editing for non-draft fields in `mutateFormDataBeforeSave`
    // or relying on disable() in resource form schema.
    // The Resource form schema already has disable logic for 'type'.
}
