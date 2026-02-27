<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Accounting\Filament\Actions\RegisterPaymentAction;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Widgets\SettlementSummaryWidget;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;

/**
 * @extends ViewRecord<\Kezi\Sales\Models\Invoice>
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // PDF Actions - Available for all invoices (draft and posted)
            ActionGroup::make([
                Action::make('viewPdf')
                    ->label(__('accounting::invoice.view_pdf'))
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('downloadPdf')
                    ->label(__('accounting::invoice.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
            ])
                ->label(__('accounting::invoice.pdf'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->button(),

            RegisterPaymentAction::make()
                ->documentType('invoice')
                ->paymentType(PaymentType::Inbound)
                ->partnerId(fn (Invoice $record) => $record->customer_id)
                ->visible(
                    fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DocsAction::make('customer-invoices'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementSummaryWidget::class,
        ];
    }
}
