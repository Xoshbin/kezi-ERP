<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('payments'),
            DocsAction::make('understanding-reversals'),
            DocsAction::make('understanding-advanced-payments'),
        ];
    }
}
