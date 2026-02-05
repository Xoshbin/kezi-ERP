<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\WithholdingTaxTypeResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListWithholdingTaxTypes extends ListRecords
{
    use Translatable;

    protected static string $resource = WithholdingTaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-withholding-tax'),
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('accounting::withholding_tax.pages.list');
    }
}
