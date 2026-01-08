<?php

namespace Modules\Manufacturing\Filament\Resources\WorkCenterResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Manufacturing\Filament\Resources\WorkCenterResource;

class CreateWorkCenter extends CreateRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;
        $data['currency_code'] = auth()->user()->currentCompany->currency->code;

        return $data;
    }
}
