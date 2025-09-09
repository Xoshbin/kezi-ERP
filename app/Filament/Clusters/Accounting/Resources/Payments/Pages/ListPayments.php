<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use App\Filament\Actions\DocsAction;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('payments'),
        ];
    }
}
