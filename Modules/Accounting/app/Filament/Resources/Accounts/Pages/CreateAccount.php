<?php

namespace Modules\Accounting\Filament\Resources\Accounts\Pages;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\Accounts\AccountResource;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $accountService = new \Modules\Accounting\Services\AccountService();

        return $accountService->create($data);
    }
}
