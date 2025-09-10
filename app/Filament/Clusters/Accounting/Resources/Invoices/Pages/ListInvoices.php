<?php

namespace App\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use App\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use App\Filament\Actions\DocsAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('customer-invoices'),
        ];
    }
}
