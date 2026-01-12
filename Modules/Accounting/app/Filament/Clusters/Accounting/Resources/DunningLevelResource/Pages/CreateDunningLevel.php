<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Modules\Accounting\DataTransferObjects\DunningLevelDTO;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

class CreateDunningLevel extends CreateRecord
{
    protected static string $resource = DunningLevelResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \Modules\Accounting\Models\User $user */
        $user = Auth::user();

        $data['company_id'] = $user->company_id;

        $dto = DunningLevelDTO::fromArray($data);

        return app(CreateDunningLevelAction::class)->execute($dto);
    }
}
