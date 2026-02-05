<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Kezi\Accounting\DataTransferObjects\DunningLevelDTO;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\DunningLevel>
 */
class CreateDunningLevel extends CreateRecord
{
    protected static string $resource = DunningLevelResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $data['company_id'] = Filament::getTenant()->id;

        $dto = DunningLevelDTO::fromArray($data);

        return app(CreateDunningLevelAction::class)->execute($dto);
    }
}
