<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\Taxes\Pages;

use App\Filament\Clusters\Settings\Resources\Taxes\TaxResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateTax extends CreateRecord
{
    use Translatable;

    protected static string $resource = TaxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('tax.pages.create');
    }
}
