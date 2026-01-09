<?php

namespace Modules\Inventory\Filament\Resources\LandedCostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Actions\LandedCost\PostLandedCostAction;
use Modules\Inventory\Filament\Resources\LandedCostResource;

class EditLandedCost extends EditRecord
{
    protected static string $resource = LandedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('post')
                ->label('Post')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (PostLandedCostAction $action) {
                    $action->execute($this->getRecord());
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                })
                ->visible(fn () => $this->getRecord()->status === \Modules\Inventory\Enums\Inventory\LandedCostStatus::Draft),
            Actions\DeleteAction::make(),
        ];
    }
}
