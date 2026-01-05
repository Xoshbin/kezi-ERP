<?php

namespace Modules\Accounting\Filament\Resources\WithholdingTax\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\WithholdingTax\WithholdingTaxTypeResource;

class CreateWithholdingTaxType extends CreateRecord
{
    use Translatable;

    protected static string $resource = WithholdingTaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('accounting::withholding_tax.pages.create');
    }
}
