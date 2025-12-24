<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\Partner;

class CustomerFinancialWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof Partner) {
            return [];
        }

        /** @var Partner $partner */
        $partner = $this->record;

        // Only show for customers and both types
        if (! in_array($partner->type, [\Modules\Foundation\Enums\Partners\PartnerType::Customer, \Modules\Foundation\Enums\Partners\PartnerType::Both])) {
            return [];
        }

        $outstandingBalance = $partner->getCustomerOutstandingBalance();
        $overdueBalance = $partner->getCustomerOverdueBalance();
        $dueIn7Days = $partner->getCustomerDueWithinDays(7);
        $avgPaymentDays = $partner->getCustomerAveragePaymentDays();

        $stats = [];

        // Total Outstanding - like "Total To Pay" in image
        $overdueText = $overdueBalance->isZero() ? '' : __('accounting::partner.widgets.includes_overdue', ['amount' => $overdueBalance->formatTo('en_US')]);
        $stats[] = Stat::make(__('accounting::partner.widgets.total_outstanding'), $outstandingBalance->formatTo('en_US'))
            ->description($overdueText)
            ->descriptionIcon('heroicon-m-banknotes')
            ->color($outstandingBalance->isZero() ? 'gray' : 'success')
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        // Due Within 7 Days - like in image
        $stats[] = Stat::make(__('accounting::partner.widgets.due_within_7_days'), $dueIn7Days->formatTo('en_US'))
            ->description(__('accounting::partner.widgets.immediate_attention'))
            ->descriptionIcon('heroicon-m-clock')
            ->color($dueIn7Days->isZero() ? 'gray' : 'warning')
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        // Average Payment Time - like in image
        $paymentTimeColor = $avgPaymentDays === 0 ? 'gray' :
                           ($avgPaymentDays <= 30 ? 'success' :
                           ($avgPaymentDays <= 60 ? 'warning' : 'danger'));

        $stats[] = Stat::make(__('accounting::partner.widgets.average_payment_time'), $avgPaymentDays.' '.__('accounting::partner.widgets.days'))
            ->description(__('accounting::partner.widgets.payment_performance'))
            ->descriptionIcon('heroicon-m-chart-bar')
            ->color($paymentTimeColor)
            ->extraAttributes([
                'class' => 'text-sm',
            ]);

        // This Month Received - simplified version
        $monthlyReceived = $partner->getMonthlyTransactionValue();
        $stats[] = Stat::make(__('accounting::partner.widgets.received_this_month'), $monthlyReceived->formatTo('en_US'))
            ->description(__('accounting::partner.widgets.current_month_activity'))
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color($monthlyReceived->isZero() ? 'gray' : 'success')
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
