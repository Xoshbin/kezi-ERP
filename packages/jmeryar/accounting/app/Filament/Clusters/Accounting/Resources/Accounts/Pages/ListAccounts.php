<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Accounts\AccountResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;

class ListAccounts extends ListRecords
{
    use Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-accounts'),
            LocaleSwitcher::make(),
            CreateAction::make()
                ->label(__('filament.actions.create').' '.__('accounting::account.label')),
        ];
    }
}
