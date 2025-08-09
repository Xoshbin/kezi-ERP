<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Services\AccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

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
