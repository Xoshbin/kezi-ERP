<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Widgets;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;

class AgingAnalysisWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof VendorBill) {
            return [];
        }

        $vendorBill = $this->record;

        // Only show aging for posted vendor bills that are not fully paid
        if ($vendorBill->status !== VendorBillStatus::Posted || $vendorBill->paymentState === \Modules\Foundation\Enums\Shared\PaymentState::Paid) {
            return [
                Stat::make(__('vendor_bill.aging_widget.status'), __('vendor_bill.aging_widget.not_applicable'))
                    ->description(__('vendor_bill.aging_widget.not_applicable_desc'))
                    ->color('gray'),
            ];
        }

        $daysOutstanding = $this->calculateDaysOutstanding($vendorBill);
        $outstandingAmount = $this->calculateOutstandingAmount($vendorBill);
        $agingBucket = $this->getAgingBucket($daysOutstanding);

        return [
            Stat::make(__('vendor_bill.aging_widget.days_outstanding'), $daysOutstanding)
                ->description(__('vendor_bill.aging_widget.days_since_due'))
                ->color($this->getDaysOutstandingColor($daysOutstanding))
                ->icon('heroicon-o-clock'),

            Stat::make(__('vendor_bill.aging_widget.outstanding_amount'), $this->formatMoney($outstandingAmount))
                ->description(__('vendor_bill.aging_widget.remaining_balance'))
                ->color($this->getOutstandingAmountColor($daysOutstanding))
                ->icon('heroicon-o-currency-dollar'),

            Stat::make(__('vendor_bill.aging_widget.aging_bucket'), $agingBucket['label'])
                ->description(__('vendor_bill.aging_widget.aging_category'))
                ->color($agingBucket['color'])
                ->icon('heroicon-o-chart-bar'),

            Stat::make(__('vendor_bill.aging_widget.payment_progress'), $this->getPaymentProgress($vendorBill))
                ->description(__('vendor_bill.aging_widget.payment_status'))
                ->color($this->getPaymentProgressColor($vendorBill))
                ->icon('heroicon-o-banknotes'),
        ];
    }

    private function calculateDaysOutstanding(VendorBill $vendorBill): int
    {
        if (! $vendorBill->due_date) {
            return 0;
        }

        $dueDate = Carbon::parse($vendorBill->due_date);
        $today = Carbon::today();

        return (int) max(0, $today->diffInDays($dueDate, false));
    }

    private function calculateOutstandingAmount(VendorBill $vendorBill): Money
    {
        $totalAmount = $vendorBill->total_amount;
        $paidAmount = $vendorBill->getPaidAmount();

        return $totalAmount->minus($paidAmount);
    }

    /**
     * @return array{label: string, color: string}
     */
    private function getAgingBucket(int $daysOutstanding): array
    {
        if ($daysOutstanding <= 0) {
            return [
                'label' => __('vendor_bill.aging_widget.current'),
                'color' => 'success',
            ];
        } elseif ($daysOutstanding <= 30) {
            return [
                'label' => __('vendor_bill.aging_widget.bucket_1_30'),
                'color' => 'warning',
            ];
        } elseif ($daysOutstanding <= 60) {
            return [
                'label' => __('vendor_bill.aging_widget.bucket_31_60'),
                'color' => 'danger',
            ];
        } elseif ($daysOutstanding <= 90) {
            return [
                'label' => __('vendor_bill.aging_widget.bucket_61_90'),
                'color' => 'danger',
            ];
        } else {
            return [
                'label' => __('vendor_bill.aging_widget.bucket_90_plus'),
                'color' => 'gray',
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

    private function getPaymentProgress(VendorBill $vendorBill): string
    {
        $totalAmount = $vendorBill->total_amount;
        $paidAmount = $vendorBill->getPaidAmount();

        if ($totalAmount->isZero()) {
            return '100%';
        }

        $percentage = $paidAmount->dividedBy($totalAmount->getAmount(), RoundingMode::HALF_UP)->multipliedBy(100);

        return \Modules\Foundation\Support\NumberFormatter::formatPercentage($percentage->getAmount()->toFloat(), 1);
    }

    private function getPaymentProgressColor(VendorBill $vendorBill): string
    {
        return match ($vendorBill->paymentState) {
            \Modules\Foundation\Enums\Shared\PaymentState::NotPaid => 'gray',
            \Modules\Foundation\Enums\Shared\PaymentState::PartiallyPaid => 'warning',
            \Modules\Foundation\Enums\Shared\PaymentState::Paid => 'success',
        };
    }

    private function formatMoney(Money $money): string
    {
        return \Modules\Foundation\Support\NumberFormatter::formatMoney($money);
    }

    protected function getColumns(): int
    {
        return 4;
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
