<?php

namespace App\Filament\Clusters\Accounting\Resources\Payments\Pages;

use App\Filament\Clusters\Accounting\Resources\Payments\PaymentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('payments_docs')
                ->label(__('Payments Guide'))
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->url(route('docs.payments'))
                ->openUrlInNewTab(),
        ];
    }
}
