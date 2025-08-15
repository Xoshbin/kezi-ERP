<?php

namespace App\Filament\Resources\Partners\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Partners\PartnerResource;
use App\Filament\Resources\Partners\Widgets\CustomerFinancialWidget;
use App\Filament\Resources\Partners\Widgets\VendorFinancialWidget;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartner extends EditRecord
{
    protected static string $resource = PartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // CustomerFinancialWidget::class,
            VendorFinancialWidget::class,
        ];
    }
}
