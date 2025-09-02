<?php

namespace App\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Facades\Filament;
use Exception;
use App\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Add company_id from tenant context
        $tenant = Filament::getTenant();
        if (!$tenant) {
            throw new Exception("No tenant set when creating Product");
        }
        $data['company_id'] = $tenant->id;

        return static::getModel()::create($data);
    }
}
