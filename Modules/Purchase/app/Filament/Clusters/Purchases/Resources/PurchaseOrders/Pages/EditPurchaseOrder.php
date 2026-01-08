<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Foundation\Enums\Incoterm;
use Modules\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Modules\Purchase\Actions\Purchases\UpdatePurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Modules\Purchase\DataTransferObjects\Purchases\PurchaseOrderLineDTO;
use Modules\Purchase\DataTransferObjects\Purchases\UpdatePurchaseOrderDTO;
use Modules\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseOrderService;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_rfq')
                ->label(__('purchase::purchase_orders.actions.send_rfq'))
                ->icon('heroicon-o-paper-airplane')
                ->color('blue')
                ->visible(fn () => $this->record->status === PurchaseOrderStatus::RFQ)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->sendRFQ($this->record, Auth::user());

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.rfq_sent'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('send')
                ->label(__('purchase::purchase_orders.actions.send'))
                ->icon('heroicon-o-paper-airplane')
                ->color('blue')
                ->visible(fn () => $this->record->status === PurchaseOrderStatus::Draft)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->send($this->record, Auth::user());

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.sent'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('confirm')
                ->label(__('purchase::purchase_orders.actions.confirm'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canBeConfirmed())
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->confirm($this->record, Auth::user());

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.confirmed'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('ready_to_receive')
                ->label(__('purchase::purchase_orders.actions.ready_to_receive'))
                ->icon('heroicon-o-truck')
                ->color('blue')
                ->visible(fn () => $this->record->status === PurchaseOrderStatus::Confirmed)
                ->requiresConfirmation()
                ->modalHeading(__('purchase::purchase_orders.actions.ready_to_receive_confirmation_title'))
                ->modalDescription(__('purchase::purchase_orders.actions.ready_to_receive_confirmation_description'))
                ->action(function () {
                    $this->record->status = PurchaseOrderStatus::ToReceive;
                    $this->record->save();

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.ready_to_receive'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('create_bill')
                ->label(__('purchase::purchase_orders.actions.create_bill'))
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->visible(fn () => $this->record->canCreateBill())
                ->requiresConfirmation()
                ->modalHeading(__('purchase::purchase_orders.actions.create_bill_confirmation_title'))
                ->modalDescription(__('purchase::purchase_orders.actions.create_bill_confirmation_description'))
                ->action(function () {
                    try {
                        // Generate a unique bill reference
                        $billReference = app(\Modules\Foundation\Services\SequenceService::class)->getNextVendorBillNumber(
                            $this->record->company,
                            Carbon::today()
                        );

                        // Create the DTO for vendor bill creation
                        $dto = new CreateVendorBillFromPurchaseOrderDTO(
                            purchase_order_id: $this->record->id,
                            bill_reference: $billReference,
                            bill_date: Carbon::today()->format('Y-m-d'),
                            accounting_date: Carbon::today()->format('Y-m-d'),
                            due_date: null, // Will be calculated based on payment terms if any
                            created_by_user_id: Auth::id(),
                            payment_term_id: null, // Could be enhanced to copy from vendor default
                            copy_all_lines: true
                        );

                        // Create the vendor bill using the existing action
                        $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

                        Notification::make()
                            ->title(__('purchase::purchase_orders.notifications.bill_created_successfully'))
                            ->body(__('purchase::purchase_orders.notifications.bill_created_body', ['reference' => $vendorBill->bill_reference]))
                            ->success()
                            ->send();

                        // Redirect to the newly created vendor bill edit page
                        $this->redirect(route('filament.jmeryar.accounting.resources.vendor-bills.edit', [
                            'tenant' => Filament::getTenant(),
                            'record' => $vendorBill->id,
                        ]));
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('purchase::purchase_orders.notifications.bill_creation_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('mark_done')
                ->label(__('purchase::purchase_orders.actions.mark_done'))
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => $this->record->status === PurchaseOrderStatus::FullyBilled)
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->markAsDone($this->record, Auth::user());

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.marked_done'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            Action::make('cancel')
                ->label(__('purchase::purchase_orders.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->action(function () {
                    app(PurchaseOrderService::class)->cancel($this->record, Auth::user());

                    Notification::make()
                        ->title(__('purchase::purchase_orders.notifications.cancelled'))
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make()
                ->visible(fn () => in_array($this->record->status, [PurchaseOrderStatus::RFQ, PurchaseOrderStatus::Draft])),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record instanceof PurchaseOrder) {
            return $data;
        }

        $record->loadMissing('lines', 'currency');

        $linesData = $record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price, // Keep as Money object for MoneyInput
                'tax_id' => $line->tax_id,
                'expected_delivery_date' => $line->expected_delivery_date?->format('Y-m-d'),
                'notes' => $line->notes,
            ];
        })->toArray();

        $data['lines'] = $linesData;

        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        if (! $record instanceof PurchaseOrder) {
            throw new \InvalidArgumentException('Expected PurchaseOrder record');
        }

        // Create line DTOs from form data
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new PurchaseOrderLineDTO(
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
        $dto = new UpdatePurchaseOrderDTO(
            purchaseOrder: $record,
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            po_date: $data['po_date'],
            lines: $lineDTOs,
            reference: $data['reference'] ?? null,
            expected_delivery_date: $data['expected_delivery_date'] ?? null,
            exchange_rate_at_creation: $data['exchange_rate_at_creation'] ?? null,
            notes: $data['notes'] ?? null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            delivery_location_id: $data['delivery_location_id'] ?? null,
            incoterm: isset($data['incoterm']) ? Incoterm::tryFrom($data['incoterm']) : null,
            status: isset($data['status']) ? PurchaseOrderStatus::from($data['status']) : null,
        );

        try {
            return app(UpdatePurchaseOrderAction::class)->execute($dto);
        } catch (\Modules\Foundation\Exceptions\UpdateNotAllowedException $e) {
            Notification::make()
                ->title(__('purchase::purchase_orders.notifications.update_not_allowed'))
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
