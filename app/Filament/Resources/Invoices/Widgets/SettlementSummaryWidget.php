<?php

namespace App\Filament\Resources\Invoices\Widgets;

use NumberFormatter;
use App\Models\Invoice;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class SettlementSummaryWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Invoice) {
            return [];
        }

        $invoice = $this->record;

        // Only show settlement summary for posted invoices
        if ($invoice->status !== InvoiceStatus::Posted) {
            return [
                Stat::make(__('invoice.settlement_widget.status'), __('invoice.settlement_widget.not_posted'))
                    ->description(__('invoice.settlement_widget.not_posted_desc'))
                    ->color('gray'),
            ];
        }

        $totalAmount = $invoice->total_amount;
        $paidAmount = $invoice->getPaidAmount();
        $outstandingBalance = $totalAmount->minus($paidAmount);
        $lastPaymentDate = $this->getLastPaymentDate($invoice);
        $paymentMethodBreakdown = $this->getPaymentMethodBreakdown($invoice);

        return [
            Stat::make(__('invoice.settlement_widget.total_amount'), $this->formatMoney($totalAmount))
                ->description(__('invoice.settlement_widget.invoice_total'))
                ->color('info')
                ->icon('heroicon-o-document-text'),

            Stat::make(__('invoice.settlement_widget.paid_amount'), $this->formatMoney($paidAmount))
                ->description(__('invoice.settlement_widget.total_paid'))
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make(__('invoice.settlement_widget.outstanding_balance'), $this->formatMoney($outstandingBalance))
                ->description(__('invoice.settlement_widget.remaining_due'))
                ->color($outstandingBalance->isZero() ? 'success' : 'warning')
                ->icon('heroicon-o-exclamation-triangle'),

            // Stat::make(__('invoice.settlement_widget.last_payment'), $lastPaymentDate)
            //     ->description(__('invoice.settlement_widget.most_recent_payment'))
            //     ->color($lastPaymentDate === __('invoice.settlement_widget.no_payments') ? 'gray' : 'info')
            //     ->icon('heroicon-o-clock'),

            // Stat::make(__('invoice.settlement_widget.payment_count'), $this->getPaymentCount($invoice))
            //     ->description(__('invoice.settlement_widget.total_payments'))
            //     ->color('info')
            //     ->icon('heroicon-o-list-bullet'),

            // Stat::make(__('invoice.settlement_widget.payment_methods'), $paymentMethodBreakdown)
            //     ->description(__('invoice.settlement_widget.payment_breakdown'))
            //     ->color('info')
            //     ->icon('heroicon-o-credit-card'),
        ];
    }

    private function getLastPaymentDate(Invoice $invoice): string
    {
        $lastPayment = $invoice->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->orderBy('payment_date', 'desc')
            ->first();

        if (!$lastPayment) {
            return __('invoice.settlement_widget.no_payments');
        }

        return Carbon::parse($lastPayment->payment_date)->format('M j, Y');
    }

    private function getPaymentCount(Invoice $invoice): string
    {
        $confirmedCount = $invoice->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->count();

        $draftCount = $invoice->payments()
            ->where('status', PaymentStatus::Draft)
            ->count();

        if ($draftCount > 0) {
            return "{$confirmedCount} + {$draftCount} " . __('invoice.settlement_widget.draft');
        }

        return (string) $confirmedCount;
    }

    private function getPaymentMethodBreakdown(Invoice $invoice): string
    {
        $payments = $invoice->payments()
            ->whereIn('status', [PaymentStatus::Confirmed, PaymentStatus::Reconciled])
            ->with('journal')
            ->get();

        if ($payments->isEmpty()) {
            return __('invoice.settlement_widget.no_payments');
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
            $breakdown .= " +" . $remaining . " " . __('invoice.settlement_widget.more');
        }

        return $breakdown ?: __('invoice.settlement_widget.various');
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

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}
