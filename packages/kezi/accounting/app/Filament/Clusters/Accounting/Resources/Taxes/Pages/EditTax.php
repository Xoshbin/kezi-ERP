<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\TaxResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\Tax>
 */
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
        return __('accounting::tax.pages.edit');
    }
}
