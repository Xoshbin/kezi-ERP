<?php

namespace App\Filament\Clusters\Accounting\Resources\Partners\Pages;

use App\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use App\Filament\Clusters\Accounting\Resources\Partners\Widgets\CustomerFinancialWidget;
use App\Filament\Clusters\Accounting\Resources\Partners\Widgets\VendorFinancialWidget;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

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
