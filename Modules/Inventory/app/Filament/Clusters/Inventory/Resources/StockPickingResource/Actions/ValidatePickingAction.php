<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Actions;

use DB;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Enums\Inventory\StockMoveStatus;
use Modules\Inventory\Enums\Inventory\StockPickingState;
use Modules\Inventory\Models\StockPicking;
use Modules\Inventory\Services\Inventory\StockReservationService;

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
            ->url(fn (StockPicking $record) => \Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource::getUrl('validate', ['record' => $record]));
    }
}
