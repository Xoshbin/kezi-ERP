<?php

namespace Modules\Accounting\Filament\Resources\WithholdingTax\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\WithholdingTax\WithholdingTaxTypeResource;

class EditWithholdingTaxType extends EditRecord
{
    use Translatable;

    protected static string $resource = WithholdingTaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('accounting::withholding_tax.pages.edit');
    }
}
