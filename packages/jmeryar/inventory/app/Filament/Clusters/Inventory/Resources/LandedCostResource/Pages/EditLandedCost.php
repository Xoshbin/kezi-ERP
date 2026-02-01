<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Inventory\Actions\LandedCost\AllocateLandedCostsAction;
use Jmeryar\Inventory\Actions\LandedCost\PostLandedCostAction;
use Jmeryar\Inventory\Enums\Inventory\LandedCostStatus;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource;

class EditLandedCost extends EditRecord
{
    protected static string $resource = LandedCostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('post')
                ->label(__('inventory::landed_cost.actions.post_landed_cost'))
                ->visible(fn ($record) => $record->status === LandedCostStatus::Draft)
                ->requiresConfirmation()
                ->action(function ($record) {
                    // Validate: Ensure at least one stock picking is attached
                    if ($record->stockPickings()->count() === 0) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('inventory::landed_cost.notifications.no_pickings'))
                            ->body(__('inventory::landed_cost.notifications.no_pickings_body'))
                            ->danger()
                            ->send();

                        return;
                    }

                    // Get all stock moves from attached pickings
                    $stockMoves = $record->stockPickings()
                        ->with('moves.productLines')
                        ->get()
                        ->pluck('moves')
                        ->flatten();

                    // Allocate costs to stock moves
                    app(AllocateLandedCostsAction::class)->execute($record, $stockMoves);

                    // Post the landed cost
                    app(PostLandedCostAction::class)->execute($record);

                    \Filament\Notifications\Notification::make()
                        ->title(__('inventory::landed_cost.notifications.posted'))
                        ->success()
                        ->send();

                    return redirect()->route('filament.admin.resources.landed-costs.edit', $record);
                })
                ->color('success'),
            Actions\DeleteAction::make(),
        ];
    }
}
