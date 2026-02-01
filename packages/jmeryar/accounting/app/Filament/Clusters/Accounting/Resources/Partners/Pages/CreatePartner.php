<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Add company_id from tenant context
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        // Extract custom fields data
        $customFieldsData = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        // Create the partner record
        $partner = static::getModel()::create($data);

        // Save custom fields if any
        if (! empty($customFieldsData)) {
            $partner->setCustomFieldValues($customFieldsData);
        }

        return $partner;
    }
}
