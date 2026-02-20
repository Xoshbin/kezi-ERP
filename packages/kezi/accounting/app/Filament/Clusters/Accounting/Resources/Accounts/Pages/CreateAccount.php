<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Accounts\AccountResource;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\Account>
 */
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
        return __('accounting::common.create').' '.__('accounting::account.label');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $accountService = new \Kezi\Accounting\Services\AccountService;

        return $accountService->create($data);
    }
}
