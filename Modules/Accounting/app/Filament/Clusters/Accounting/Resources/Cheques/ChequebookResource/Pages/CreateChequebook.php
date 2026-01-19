<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource;

class CreateChequebook extends CreateRecord
{
    protected static string $resource = ChequebookResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
