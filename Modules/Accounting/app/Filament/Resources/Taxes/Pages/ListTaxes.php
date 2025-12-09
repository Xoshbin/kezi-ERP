<?php

namespace Modules\Accounting\Filament\Resources\Taxes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\Taxes\TaxResource;

class ListTaxes extends ListRecords
{
    use Translatable;

    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('accounting::tax.pages.list');
    }
}
