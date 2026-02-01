<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource;

class ListCurrencyRevaluations extends ListRecords
{
    protected static string $resource = CurrencyRevaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('currency-revaluation'),
            Actions\CreateAction::make(),
        ];
    }
}
