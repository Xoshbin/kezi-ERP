<?php

namespace App\Filament\Clusters\HumanResources\Resources\Positions\Pages;

use Filament\Facades\Filament;
use Log;
use Illuminate\Database\Eloquent\Model;
use Exception;
use App\Filament\Clusters\HumanResources\Resources\Positions\PositionResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

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
        $data['company_id'] = Filament::getTenant()->id;

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
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
