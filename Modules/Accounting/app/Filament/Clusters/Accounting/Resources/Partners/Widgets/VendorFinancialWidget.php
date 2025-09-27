<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Widgets;

use App\Enums\Partners\PartnerType;
use App\Models\Partner;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class VendorFinancialWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof Partner) {
            return [];
        }

        /** @var Partner $partner */
        $partner = $this->record;

        // Only show for vendors and both types
        if (! in_array($partner->type, [PartnerType::Vendor, PartnerType::Both])) {
            return [];
        }

        $outstandingBalance = $partner->getVendorOutstandingBalance();
        $overdueBalance = $partner->getVendorOverdueBalance();
        $dueIn7Days = $partner->getVendorDueWithinDays(7);
        // $avgPaymentDays = $partner->getVendorAveragePaymentDays();

        $stats = [];

        // Total To Pay - like in image
        $overdueText = $overdueBalance->isZero() ? '' : __('partner.widgets.includes_overdue', ['amount' => $overdueBalance->formatTo('en_US')]);
        $stats[] = Stat::make(__('partner.widgets.total_to_pay'), $outstandingBalance->formatTo('en_US'))
            ->description($overdueText)
            ->descriptionIcon('heroicon-m-credit-card')
            ->color($outstandingBalance->isZero() ? 'gray' : 'danger')
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        // Due Within 7 Days - like in image
        $stats[] = Stat::make(__('partner.widgets.due_within_7_days'), $dueIn7Days->formatTo('en_US'))
            ->description(__('partner.widgets.urgent_payments'))
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($dueIn7Days->isZero() ? 'gray' : 'warning')
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        // Average Payment Time - like in image
        // $paymentTimeColor = $avgPaymentDays === 0 ? 'gray' :
        //                    ($avgPaymentDays <= 30 ? 'success' :
        //                    ($avgPaymentDays <= 60 ? 'warning' : 'danger'));

        // $stats[] = Stat::make(__('partner.widgets.average_payment_time'), $avgPaymentDays . ' ' . __('partner.widgets.days'))
        //     ->description(__('partner.widgets.our_payment_performance'))
        //     ->descriptionIcon('heroicon-m-chart-bar')
        //     ->color($paymentTimeColor)
        //     ->extraAttributes([
        //         'class' => 'text-sm'
        //     ]);

        // Paid Last Month - simplified version
        $monthlyPaid = $partner->getMonthlyTransactionValue();
        $stats[] = Stat::make(__('partner.widgets.paid_last_month'), $monthlyPaid->formatTo('en_US'))
            ->description(__('partner.widgets.last_month_payments'))
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color($monthlyPaid->isZero() ? 'gray' : 'info')
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        return $stats;
    }

    protected function getColumns(): int
    {
        return 4; // Match the image layout
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getPollingInterval(): ?string
    {
        return null;
    }
}
