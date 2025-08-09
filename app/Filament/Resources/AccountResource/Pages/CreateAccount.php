<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Services\AccountService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccount extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
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
