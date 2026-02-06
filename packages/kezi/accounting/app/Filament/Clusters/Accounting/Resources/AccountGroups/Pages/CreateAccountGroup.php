<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\AccountGroupResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\AccountGroup>
 */
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
