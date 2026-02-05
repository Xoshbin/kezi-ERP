<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource;

/**
 * @extends EditRecord<\Kezi\Payment\Models\Chequebook>
 */
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
