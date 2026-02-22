<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Accounting\Filament\Actions\RegisterPaymentAction;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Purchase\Enums\Purchases\VendorBillStatus;
use Kezi\Purchase\Models\VendorBill;

/**
 * @extends ViewRecord<\Kezi\Purchase\Models\VendorBill>
 */
class ViewVendorBill extends ViewRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            RegisterPaymentAction::make()
                ->label(__('accounting::bill.actions.register_payment'))
                ->modalHeading(__('accounting::bill.payments_relation_manager.create_payment'))
                ->modalDescription(__('accounting::bill.register_payment.description'))
                ->documentType('vendor_bill')
                ->paymentType(PaymentType::Outbound)
                ->partnerId(fn (VendorBill $record) => $record->vendor_id)
                ->visible(
                    fn (VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DocsAction::make('vendor-bills'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementSummaryWidget::class,
        ];
    }
}
