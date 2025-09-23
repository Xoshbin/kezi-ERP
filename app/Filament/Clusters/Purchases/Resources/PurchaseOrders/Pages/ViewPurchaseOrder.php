<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

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
            ->visible(function (): bool {
                /** @var PurchaseOrder $record */
                $record = $this->getRecord();
                return $record->status->canCreateBill();
            })
            ->action(function (): void {
                /** @var PurchaseOrder $record */
                $record = $this->getRecord();

                // Redirect to VendorBill creation with PO ID parameter
                $this->redirect(
                    VendorBillResource::getUrl('create', [
                        'purchase_order_id' => $record->id,
                    ], tenant: Filament::getTenant())
                );
            });
    }
}
