<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use Filament\Actions\Action;
use Jmeryar\Inventory\Models\StockPicking;

class ValidatePickingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'validate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Validate / Process'))
            ->icon('heroicon-o-arrow-right-circle')
            ->color('success')
            ->url(fn (StockPicking $record) => \Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource::getUrl('validate', ['record' => $record]));
    }
}
