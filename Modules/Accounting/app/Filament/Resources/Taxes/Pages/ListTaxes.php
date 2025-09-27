<?php

namespace App\Filament\Clusters\Settings\Resources\Taxes\Pages;

use App\Filament\Clusters\Settings\Resources\Taxes\TaxResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
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
            CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('tax.pages.list');
    }
}
