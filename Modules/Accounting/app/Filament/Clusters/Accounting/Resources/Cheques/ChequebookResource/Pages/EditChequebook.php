<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource;

class EditChequebook extends EditRecord
{
    protected static string $resource = ChequebookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
