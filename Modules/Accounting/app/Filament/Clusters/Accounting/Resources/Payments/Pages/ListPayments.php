<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Modules\Foundation\Filament\Actions\DocsAction;

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
