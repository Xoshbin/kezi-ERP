<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages;

use Exception;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\AccountResource;
use Modules\Accounting\Models\Account;

class EditAccount extends EditRecord
{
    use Translatable;

    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make()
                ->action(function (Model $record) {
                    if (! $record instanceof Account) {
                        throw new Exception('Invalid record type');
                    }
                    $accountService = new \Modules\Accounting\Services\AccountService;
                    $accountService->delete($record);
                    $this->redirect(AccountResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant?->getKey() ?? 0;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Account) {
            throw new Exception('Invalid record type');
        }

        $accountService = new \Modules\Accounting\Services\AccountService;

        return $accountService->update($record, $data);
    }
}
