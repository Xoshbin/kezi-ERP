<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource;

class ListCurrencyRevaluations extends ListRecords
{
    protected static string $resource = CurrencyRevaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
