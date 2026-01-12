<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Accounting\Actions\Dunning\UpdateDunningLevelAction;
use Modules\Accounting\DataTransferObjects\DunningLevelDTO;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

class EditDunningLevel extends EditRecord
{
    protected static string $resource = DunningLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Ensure company_id is preserved if not in form, or set from record
        $data['company_id'] = $record->company_id;

        $dto = DunningLevelDTO::fromArray($data);

        /** @var \Modules\Accounting\Models\DunningLevel $record */
        return app(UpdateDunningLevelAction::class)->execute($record, $dto);
    }
}
