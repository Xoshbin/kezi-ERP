<?php

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxes extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('tax.pages.list');
    }
}
