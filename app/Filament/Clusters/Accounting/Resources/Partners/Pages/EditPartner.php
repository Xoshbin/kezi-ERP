<?php

namespace App\Filament\Clusters\Accounting\Resources\Partners\Pages;

use App\Filament\Clusters\Accounting\Resources\Partners\PartnerResource;
use App\Filament\Clusters\Accounting\Resources\Partners\Widgets\VendorFinancialWidget;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
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
