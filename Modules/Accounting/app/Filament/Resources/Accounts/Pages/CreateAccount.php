<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\Accounts\Pages;

use App\Filament\Clusters\Settings\Resources\Accounts\AccountResource;
use App\Services\AccountService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

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
        return __('filament.actions.create').' '.__('account.label');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $accountService = new AccountService;

        return $accountService->create($data);
    }
}
