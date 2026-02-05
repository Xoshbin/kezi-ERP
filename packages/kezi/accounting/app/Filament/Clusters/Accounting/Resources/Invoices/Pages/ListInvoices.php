<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('customer-invoices'),
            DocsAction::make('understanding-reversals'),
        ];
    }
}
