<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\FiscalYearResource;

class ListFiscalYears extends ListRecords
{
    protected static string $resource = FiscalYearResource::class;

    /**
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-fiscal-years'),
            CreateAction::make(),
        ];
    }
}
