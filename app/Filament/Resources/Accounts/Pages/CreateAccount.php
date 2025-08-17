<?php

namespace App\Filament\Resources\Accounts\Pages;

use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use App\Filament\Resources\Accounts\AccountResource;
use App\Services\AccountService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccount extends CreateRecord
{
    use Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    public function getTitle(): string
    {
        return __('filament.actions.create') . ' ' . __('account.label');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $accountService = new AccountService();
        return $accountService->create($data);
    }
}
