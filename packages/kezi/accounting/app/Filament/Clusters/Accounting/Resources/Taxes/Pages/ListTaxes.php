<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\TaxResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListTaxes extends ListRecords
{
    use Translatable;

    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('tax-management'),
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('accounting::tax.pages.list');
    }
}
