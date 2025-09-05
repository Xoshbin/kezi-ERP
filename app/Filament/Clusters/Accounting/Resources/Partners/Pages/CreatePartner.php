<?php

namespace App\Filament\Clusters\Accounting\Resources\Partners\Pages;

use App\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePartner extends CreateRecord
{
    protected static string $resource = PartnerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Add company_id from tenant context
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return static::getModel()::create($data);
    }
}
