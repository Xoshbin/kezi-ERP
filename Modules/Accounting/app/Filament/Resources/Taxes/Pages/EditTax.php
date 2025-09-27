<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\Taxes\Pages;

use App\Filament\Clusters\Settings\Resources\Taxes\TaxResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

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
