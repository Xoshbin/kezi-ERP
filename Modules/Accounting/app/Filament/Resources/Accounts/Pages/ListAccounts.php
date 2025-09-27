<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\Accounts\Pages;

use App\Filament\Clusters\Settings\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListAccounts extends ListRecords
{
    use Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make()
                ->label(__('filament.actions.create').' '.__('account.label')),
        ];
    }
}
