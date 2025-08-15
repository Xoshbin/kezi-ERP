<?php

namespace App\Filament\Resources\Taxes\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Taxes\TaxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTax extends EditRecord
{
    use Translatable;

    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('tax.pages.edit');
    }
}
