<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AccountGroups\AccountGroupResource;
use Kezi\Foundation\Filament\Actions\DocsAction;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListAccountGroups extends ListRecords
{
    use Translatable;

    protected static string $resource = AccountGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DocsAction::make('account-groups'),
            CreateAction::make()
                ->label(__('filament.actions.create').' '.__('accounting::account_group.label')),
        ];
    }
}
