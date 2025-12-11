<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Modules\Sales\Enums\Sales\SalesOrderStatus;
use Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Models\SalesOrder;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label(__('sales::sales_orders.actions.confirm'))
                ->requiresConfirmation()
                ->color('success')
                ->action(function (SalesOrder $record) {
                    app(ConfirmSalesOrderAction::class)->execute($record, auth()->user());
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                })
                ->visible(fn (SalesOrder $record) => $record->status === SalesOrderStatus::Draft),
            Actions\Action::make('create_invoice')
                ->label(__('sales::sales_orders.actions.create_invoice'))
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('invoice_date')
                        ->label('Invoice Date')
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date')
                        ->required()
                        ->default(now()->addDays(30)),
                    \Filament\Forms\Components\Select::make('default_income_account_id')
                        ->label('Default Income Account')
                        ->options(\Modules\Accounting\Models\Account::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (SalesOrder $record, array $data) {
                    $dto = new \Modules\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO(
                        salesOrder: $record,
                        invoice_date: \Carbon\Carbon::parse($data['invoice_date']),
                        due_date: \Carbon\Carbon::parse($data['due_date']),
                        default_income_account_id: $data['default_income_account_id'],
                    );
                    app(\Modules\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction::class)->execute($dto);
                    
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                })
                ->visible(fn (SalesOrder $record) => $record->canCreateInvoice()),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
