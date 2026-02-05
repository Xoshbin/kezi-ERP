<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages;

use \Filament\Actions\DeleteAction;
use \Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Widgets\CustomerFinancialWidget;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Widgets\VendorFinancialWidget;

/**
 * @extends ViewRecord<\Kezi\Foundation\Models\Partner>
 */
class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerFinancialWidget::class,
            VendorFinancialWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Additional widgets can be added here if needed
        ];
    }
}
