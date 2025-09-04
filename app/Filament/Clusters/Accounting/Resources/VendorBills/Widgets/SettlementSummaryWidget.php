<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills\Widgets;

use App\Enums\Payments\PaymentStatus;
use App\Enums\Purchases\VendorBillStatus;
use App\Models\VendorBill;
use App\Models\Payment;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use NumberFormatter;

class SettlementSummaryWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof VendorBill) {
            return [];
        }

        $vendorBill = $this->record;

        // Only show settlement summary for posted vendor bills
        if ($vendorBill->status !== VendorBillStatus::Posted) {
            return [
                Stat::make(__('vendor_bill.settlement_widget.status'), __('vendor_bill.settlement_widget.not_posted'))
                    ->description(__('vendor_bill.settlement_widget.not_posted_desc'))
                    ->color('gray'),
            ];
        }

        $totalAmount = $vendorBill->total_amount;
        $paidAmount = $vendorBill->getPaidAmount();
        $outstandingBalance = $totalAmount->minus($paidAmount);
        $lastPaymentDate = $this->getLastPaymentDate($vendorBill);
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($vendorBill);

        return [
            Stat::make(__('vendor_bill.settlement_widget.total_amount'), $this->formatMoney($totalAmount))
                ->description(__('vendor_bill.settlement_widget.bill_total'))
                ->color('info')
                ->icon('heroicon-o-document-text'),

            Stat::make(__('vendor_bill.settlement_widget.paid_amount'), $this->formatMoney($paidAmount))
                ->description(__('vendor_bill.settlement_widget.total_paid'))
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make(__('vendor_bill.settlement_widget.outstanding_balance'), $this->formatMoney($outstandingBalance))
                ->description(__('vendor_bill.settlement_widget.remaining_due'))
                ->color($outstandingBalance->isZero() ? 'success' : 'warning')
                ->icon('heroicon-o-exclamation-triangle'),

            // Stat::make(__('vendor_bill.settlement_widget.last_payment'), $lastPaymentDate)
            //     ->description(__('vendor_bill.settlement_widget.most_recent_payment'))
            //     ->color($lastPaymentDate === __('vendor_bill.settlement_widget.no_payments') ? 'gray' : 'info')
            //     ->icon('heroicon-o-clock'),

            // Stat::make(__('vendor_bill.settlement_widget.payment_count'), $this->getPaymentCount($vendorBill))
            //     ->description(__('vendor_bill.settlement_widget.total_payments'))
            //     ->color('info')
            //     ->icon('heroicon-o-list-bullet'),

            // Stat::make(__('vendor_bill.settlement_widget.payment_methods'), $paymentMethodBreakdown)
            //     ->description(__('vendor_bill.settlement_widget.payment_breakdown'))
            //     ->color('info')
            //     ->icon('heroicon-o-credit-card'),
        ];
    }

    private function getLastPaymentDate(VendorBill $vendorBill): string
    {
        /** @var Payment|null $lastPayment */
        $lastPayment = $vendorBill->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->orderBy('payment_date', 'desc')
            ->first();

        if (! $lastPayment) {
            return __('vendor_bill.settlement_widget.no_payments');
        }

        return Carbon::parse($lastPayment->payment_date)->format('M j, Y');
    }

    private function getPaymentCount(VendorBill $vendorBill): string
    {
        $confirmedCount = $vendorBill->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->count();

        $draftCount = $vendorBill->payments()
            ->where('status', PaymentStatus::Draft)
            ->count();

        if ($draftCount > 0) {
            return "{$confirmedCount} + {$draftCount} ".__('vendor_bill.settlement_widget.draft');
        }

        return (string) $confirmedCount;
    }

    private function getPaymentMethodBreakdown(VendorBill $vendorBill): string
    {
        $payments = $vendorBill->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->with('journal')
            ->get();

        if ($payments->isEmpty()) {
            return __('vendor_bill.settlement_widget.no_payments');
        }

        $breakdown = $payments->groupBy('journal.name')
            ->map(function ($groupedPayments, $journalName) {
                $count = $groupedPayments->count();

                return "{$journalName} ({$count})";
            })
            ->values()
            ->take(3) // Limit to first 3 to avoid overflow
            ->implode(', ');

        $totalJournals = $payments->pluck('journal.name')->unique()->count();
        if ($totalJournals > 3) {
            $remaining = $totalJournals - 3;
            $breakdown .= ' +'.$remaining.' '.__('vendor_bill.settlement_widget.more');
        }

        return $breakdown ?: __('vendor_bill.settlement_widget.various');
    }

    private function formatMoney(Money $money): string
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);

        return $formatter->formatCurrency(
            $money->getAmount()->toFloat(),
            $money->getCurrency()->getCurrencyCode()
        );
    }

    protected function getColumns(): int
    {
        return 3;
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
