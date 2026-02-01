<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use Exception;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;

class CreatePosition extends CreateRecord
{
    use Translatable;

    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure company_id is set from the current tenant
        /** @var Company|null $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey();

        // Debug: Log the data being created
        Log::info('Creating position with data:', $data);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            Log::info('Attempting to create position with data:', $data);
            $record = parent::handleRecordCreation($data);
            Log::info('Successfully created position:', $record->toArray());

            return $record;
        } catch (Exception $e) {
            Log::error('Failed to create position:', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
