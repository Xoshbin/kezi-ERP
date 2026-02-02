<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Sales\Filament\Clusters\Sales\Resources\Quotes\QuoteResource;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->with(['partner', 'currency', 'createdBy']);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-sales-quotes'),
        ];
    }
}
