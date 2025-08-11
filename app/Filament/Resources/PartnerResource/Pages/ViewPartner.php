<?php

namespace App\Filament\Resources\PartnerResource\Pages;

use App\Filament\Resources\PartnerResource;
use App\Filament\Resources\PartnerResource\Widgets\CustomerFinancialWidget;
use App\Filament\Resources\PartnerResource\Widgets\VendorFinancialWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPartner extends ViewRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
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
