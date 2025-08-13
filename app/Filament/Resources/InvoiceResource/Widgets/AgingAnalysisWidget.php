<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use App\Enums\Sales\InvoiceStatus;
use App\Enums\Shared\PaymentState;
use App\Support\NumberFormatter;
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AgingAnalysisWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record instanceof Invoice) {
            return [];
        }

        $invoice = $this->record;

        // Only show aging for posted invoices that are not fully paid
        if ($invoice->status !== InvoiceStatus::Posted || $invoice->paymentState === PaymentState::Paid) {
            return [
                Stat::make(__('invoice.aging_widget.status'), __('invoice.aging_widget.not_applicable'))
                    ->description(__('invoice.aging_widget.not_applicable_desc'))
                    ->color('gray'),
            ];
        }

        $daysOutstanding = $this->calculateDaysOutstanding($invoice);
        $outstandingAmount = $this->calculateOutstandingAmount($invoice);
        $agingBucket = $this->getAgingBucket($daysOutstanding);

        return [
            Stat::make(__('invoice.aging_widget.days_outstanding'), $daysOutstanding)
                ->description(__('invoice.aging_widget.days_since_due'))
                ->color($this->getDaysOutstandingColor($daysOutstanding))
                ->icon('heroicon-o-clock'),

            Stat::make(__('invoice.aging_widget.outstanding_amount'), $this->formatMoney($outstandingAmount))
                ->description(__('invoice.aging_widget.remaining_balance'))
                ->color($this->getOutstandingAmountColor($daysOutstanding))
                ->icon('heroicon-o-currency-dollar'),

            Stat::make(__('invoice.aging_widget.aging_bucket'), $agingBucket['label'])
                ->description(__('invoice.aging_widget.aging_category'))
                ->color($agingBucket['color'])
                ->icon('heroicon-o-chart-bar'),

            Stat::make(__('invoice.aging_widget.payment_progress'), $this->getPaymentProgress($invoice))
                ->description(__('invoice.aging_widget.payment_status'))
                ->color($this->getPaymentProgressColor($invoice))
                ->icon('heroicon-o-banknotes'),
        ];
    }

    private function calculateDaysOutstanding(Invoice $invoice): int
    {
        if (!$invoice->due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($invoice->due_date);
        $today = Carbon::today();

        return max(0, $today->diffInDays($dueDate, false));
    }

    private function calculateOutstandingAmount(Invoice $invoice): Money
    {
        $totalAmount = $invoice->total_amount;
        $paidAmount = $invoice->getPaidAmount();

        return $totalAmount->minus($paidAmount);
    }

    private function getAgingBucket(int $daysOutstanding): array
    {
        if ($daysOutstanding <= 0) {
            return [
                'label' => __('invoice.aging_widget.current'),
                'color' => 'success'
            ];
        } elseif ($daysOutstanding <= 30) {
            return [
                'label' => __('invoice.aging_widget.bucket_1_30'),
                'color' => 'warning'
            ];
        } elseif ($daysOutstanding <= 60) {
            return [
                'label' => __('invoice.aging_widget.bucket_31_60'),
                'color' => 'danger'
            ];
        } elseif ($daysOutstanding <= 90) {
            return [
                'label' => __('invoice.aging_widget.bucket_61_90'),
                'color' => 'danger'
            ];
        } else {
            return [
                'label' => __('invoice.aging_widget.bucket_90_plus'),
                'color' => 'gray'
            ];
        }
    }

    private function getDaysOutstandingColor(int $daysOutstanding): string
    {
        if ($daysOutstanding <= 0) {
            return 'success';
        } elseif ($daysOutstanding <= 30) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    private function getOutstandingAmountColor(int $daysOutstanding): string
    {
        return $this->getDaysOutstandingColor($daysOutstanding);
    }

    private function getPaymentProgress(Invoice $invoice): string
    {
        $totalAmount = $invoice->total_amount;
        $paidAmount = $invoice->getPaidAmount();

        if ($totalAmount->isZero()) {
            return '100%';
        }

        $percentage = $paidAmount->dividedBy($totalAmount->getAmount(), RoundingMode::HALF_UP)->multipliedBy(100);

        return NumberFormatter::formatPercentage($percentage->getAmount()->toFloat(), 1);
    }

    private function getPaymentProgressColor(Invoice $invoice): string
    {
        return match($invoice->paymentState) {
            PaymentState::NotPaid => 'gray',
            PaymentState::PartiallyPaid => 'warning',
            PaymentState::Paid => 'success',
        };
    }

    private function formatMoney(Money $money): string
    {
        return NumberFormatter::formatMoney($money);
    }

    protected function getColumns(): int
    {
        return 4;
    }

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }
}
