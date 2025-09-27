<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use App\Actions\Sales\CreateDeliveryFromSalesOrderAction;
use App\Actions\Sales\CreateInvoiceFromSalesOrderAction;
use App\DataTransferObjects\Sales\CreateDeliveryFromSalesOrderDTO;
use App\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO;
use App\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesOrder extends ViewRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Action::make('create_invoice')
                ->label(__('sales_orders.actions.create_invoice'))
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->visible(fn() => $this->record->canCreateInvoice())
                ->form([
                    DatePicker::make('invoice_date')
                        ->label(__('invoices.fields.invoice_date'))
                        ->required()
                        ->default(now())
                        ->native(false),

                    DatePicker::make('due_date')
                        ->label(__('invoices.fields.due_date'))
                        ->required()
                        ->default(now()->addDays(30))
                        ->native(false),

                    Select::make('default_income_account_id')
                        ->label(__('invoices.fields.default_income_account'))
                        ->options(function () {
                            return \Modules\Accounting\Models\Account::where('company_id', Filament::getTenant()?->id)
                                ->where('account_type', 'income')
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    try {
                        $dto = new CreateInvoiceFromSalesOrderDTO(
                            salesOrder: $this->record,
                            invoice_date: Carbon::parse($data['invoice_date']),
                            due_date: Carbon::parse($data['due_date']),
                            default_income_account_id: $data['default_income_account_id'],
                        );

                        $action = app(CreateInvoiceFromSalesOrderAction::class);
                        $invoice = $action->execute($dto);

                        Notification::make()
                            ->title(__('sales_orders.notifications.invoice_created'))
                            ->success()
                            ->send();

                        return redirect()->route('filament.admin.resources.invoices.view', $invoice);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('sales_orders.notifications.invoice_creation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('create_delivery')
                ->label(__('sales_orders.actions.create_delivery'))
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->visible(fn() => $this->record->canDeliverGoods())
                ->form([
                    DatePicker::make('scheduled_date')
                        ->label(__('deliveries.fields.scheduled_date'))
                        ->required()
                        ->default(now())
                        ->native(false),

                    Toggle::make('auto_confirm')
                        ->label(__('deliveries.fields.auto_confirm'))
                        ->helperText(__('deliveries.help.auto_confirm'))
                        ->default(function () {
                            return $this->record->company->inventory_accounting_mode->autoRecordsInventory();
                        }),
                ])
                ->action(function (array $data) {
                    try {
                        $dto = new CreateDeliveryFromSalesOrderDTO(
                            salesOrder: $this->record,
                            user: auth()->user(),
                            scheduled_date: Carbon::parse($data['scheduled_date']),
                            autoConfirm: $data['auto_confirm'] ?? false,
                        );

                        $action = app(CreateDeliveryFromSalesOrderAction::class);
                        $stockMoves = $action->execute($dto);

                        Notification::make()
                            ->title(__('sales_orders.notifications.delivery_created'))
                            ->body(__('sales_orders.notifications.delivery_created_count', ['count' => $stockMoves->count()]))
                            ->success()
                            ->send();

                        // Refresh the record to show updated status
                        $this->refreshFormData([
                            'status',
                        ]);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('sales_orders.notifications.delivery_creation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
