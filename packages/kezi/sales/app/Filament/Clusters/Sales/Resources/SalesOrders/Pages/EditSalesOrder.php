<?php

namespace Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Sales\Actions\Sales\ConfirmSalesOrderAction;
use Kezi\Sales\Actions\Sales\UpdateSalesOrderAction;
use Kezi\Sales\DataTransferObjects\Sales\SalesOrderLineDTO;
use Kezi\Sales\DataTransferObjects\Sales\UpdateSalesOrderDTO;
use Kezi\Sales\Enums\Sales\SalesOrderStatus;
use Kezi\Sales\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Kezi\Sales\Models\SalesOrder;

/**
 * @extends EditRecord<\Kezi\Sales\Models\SalesOrder>
 */
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
                    try {
                        $user = auth()->user();
                        if (! $user) {
                            throw new \Exception('User must be authenticated to confirm sales order');
                        }
                        app(ConfirmSalesOrderAction::class)->execute($record, $user);

                        \Filament\Notifications\Notification::make()
                            ->title(__('sales::sales_orders.notifications.confirmed'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('sales::sales_orders.form.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (SalesOrder $record) => $record->status === SalesOrderStatus::Draft),
            Actions\Action::make('create_invoice')
                ->label(__('sales::sales_orders.actions.create_invoice'))
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('invoice_date')
                        ->label(__('sales::sales_orders.form.invoice_date'))
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\DatePicker::make('due_date')
                        ->label(__('sales::sales_orders.form.due_date'))
                        ->required()
                        ->default(now()->addDays(30)),
                    AccountSelectField::make('default_income_account_id')
                        ->label(__('sales::sales_orders.form.default_income_account'))
                        ->accountFilter('income')
                        ->required(),
                ])
                ->action(function (SalesOrder $record, array $data) {
                    $dto = new \Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceFromSalesOrderDTO(
                        salesOrder: $record,
                        invoice_date: \Carbon\Carbon::parse($data['invoice_date']),
                        due_date: \Carbon\Carbon::parse($data['due_date']),
                        default_income_account_id: $data['default_income_account_id'],
                    );
                    app(\Kezi\Sales\Actions\Sales\CreateInvoiceFromSalesOrderAction::class)->execute($dto);

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                })
                ->visible(fn (SalesOrder $record) => $record->canCreateInvoice()),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record instanceof SalesOrder) {
            return $data;
        }

        $record->loadMissing('lines', 'currency');

        $linesData = $record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_id' => $line->tax_id,
                'expected_delivery_date' => $line->expected_delivery_date,
                'notes' => $line->notes,
            ];
        })->toArray();

        $data['lines'] = $linesData;

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        if (! $record instanceof SalesOrder) {
            throw new \InvalidArgumentException('Expected SalesOrder record');
        }

        // Create line DTOs from form data
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new SalesOrderLineDTO(
                product_id: $line['product_id'],
                description: $line['description'],
                quantity: (float) $line['quantity'],
                unit_price: Money::of($line['unit_price'], $record->currency->code),
                tax_id: $line['tax_id'] ?? null,
                expected_delivery_date: isset($line['expected_delivery_date'])
                    ? Carbon::parse($line['expected_delivery_date'])
                    : null,
                notes: $line['notes'] ?? null,
            );
        }

        // Create the update DTO
        $dto = new UpdateSalesOrderDTO(
            salesOrder: $record,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            so_date: $data['so_date'],
            lines: $lineDTOs,
            reference: $data['reference'] ?? null,
            expected_delivery_date: $data['expected_delivery_date'] ?? null,
            exchange_rate_at_creation: $data['exchange_rate_at_creation'] ?? null,
            notes: $data['notes'] ?? null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            delivery_location_id: $data['delivery_location_id'] ?? null,
            incoterm: $data['incoterm'] instanceof Incoterm ? $data['incoterm'] : (isset($data['incoterm']) ? Incoterm::tryFrom($data['incoterm']) : null),
            status: $data['status'] instanceof SalesOrderStatus ? $data['status'] : (isset($data['status']) ? SalesOrderStatus::from($data['status']) : null),
        );

        try {
            return app(UpdateSalesOrderAction::class)->execute($dto);
        } catch (\Kezi\Foundation\Exceptions\UpdateNotAllowedException $e) {
            \Filament\Notifications\Notification::make()
                ->title(__('sales::sales_orders.notifications.update_not_allowed'))
                ->body($e->getMessage())
                ->warning()
                ->persistent()
                ->send();

            // Halt the update process
            $this->halt();

            // This line will never be reached due to halt(), but satisfies the return type
            throw $e;
        }
    }
}
