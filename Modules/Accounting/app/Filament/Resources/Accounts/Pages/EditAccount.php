<?php

namespace Modules\Accounting\Filament\Resources\Accounts\Pages;

use Exception;
use Filament\Actions\DeleteAction;
use Modules\Accounting\Models\Account;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Accounting\Filament\Resources\Accounts\AccountResource;

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
                    $accountService = new \Modules\Accounting\Services\AccountService();
                    $accountService->delete($record);
                    $this->redirect(AccountResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Account) {
            throw new Exception('Invalid record type');
        }

        $accountService = new \Modules\Accounting\Services\AccountService();

        return $accountService->update($record, $data);
    }
}
