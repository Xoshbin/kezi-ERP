<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Modules\Accounting\DataTransferObjects\DunningLevelDTO;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

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
