<?php

namespace App\Filament\Resources\Accounts\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Accounts\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    use Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            CreateAction::make()
                ->label(__('filament.actions.create') . ' ' . __('account.label')),
        ];
    }
}
