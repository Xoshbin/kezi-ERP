<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\AccountGroupResource;

class CreateAccountGroup extends CreateRecord
{
    use Translatable;

    protected static string $resource = AccountGroupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
