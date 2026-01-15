<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Modules\Foundation\Filament\Actions\DocsAction;

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
