<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use Exception;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;


class CreateProduct extends CreateRecord
{
    use Translatable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Add company_id from tenant context
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();
        if (! $tenant) {
            throw new Exception('No tenant set when creating Product');
        }
        $data['company_id'] = $tenant->getKey();

        return static::getModel()::create($data);
    }
}
