<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;



use Exception;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Foundation\Services\SequenceService;
use Modules\Purchase\Actions\Purchases\CreateVendorBillFromPurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillFromPurchaseOrderDTO;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return PurchaseOrderInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->getCreateBillAction(),
        ];
    }

    protected function getCreateBillAction(): Action
    {
        return Action::make('createBill')
            ->label(__('purchase_orders.actions.create_bill'))
            ->icon('heroicon-o-document-plus')
            ->color('success')
            ->visible(fn() => $this->record->status->canCreateBill())
            ->requiresConfirmation()
            ->modalHeading(__('purchase_orders.actions.create_bill_confirmation_title'))
            ->modalDescription(__('purchase_orders.actions.create_bill_confirmation_description'))
            ->action(function () {
                try {
                    // Generate unique bill reference
                    $billReference = app(\Modules\Foundation\Services\SequenceService::class)->getNextVendorBillNumber(
                        $this->record->company,
                        Carbon::today()
                    );

                    // Create DTO and execute action
                    $dto = new CreateVendorBillFromPurchaseOrderDTO(
                        purchase_order_id: $this->record->id,
                        bill_reference: $billReference,
                        bill_date: Carbon::today()->format('Y-m-d'),
                        accounting_date: Carbon::today()->format('Y-m-d'),
                        due_date: null,
                        created_by_user_id: Auth::id(),
                        payment_term_id: null,
                        copy_all_lines: true
                    );

                    $vendorBill = app(CreateVendorBillFromPurchaseOrderAction::class)->execute($dto);

                    // Show success notification and redirect
                    Notification::make()
                        ->title(__('purchase_orders.notifications.bill_created_successfully'))
                        ->body(__('purchase_orders.notifications.bill_created_body', ['reference' => $vendorBill->bill_reference]))
                        ->success()
                        ->send();

                    $this->redirect(route('filament.jmeryar.accounting.resources.vendor-bills.edit', [
                        'tenant' => Filament::getTenant(),
                        'record' => $vendorBill->id,
                    ]));
                } catch (Exception $e) {
                    Notification::make()
                        ->title(__('purchase_orders.notifications.bill_creation_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
