<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Actions\Dunning\UpdateDunningLevelAction;
use Kezi\Accounting\DataTransferObjects\DunningLevelDTO;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\DunningLevel>
 */
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

        /** @var \Kezi\Accounting\Models\DunningLevel $record */
        return app(UpdateDunningLevelAction::class)->execute($record, $dto);
    }
}
