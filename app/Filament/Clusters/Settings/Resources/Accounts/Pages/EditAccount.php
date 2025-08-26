<?php

namespace App\Filament\Clusters\Settings\Resources\Accounts\Pages;

use App\Filament\Clusters\Settings\Resources\Accounts\AccountResource;
use App\Services\AccountService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

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
                    $accountService = new AccountService();
                    $accountService->delete($record);
                    $this->redirect(AccountResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $accountService = new AccountService();
        return $accountService->update($record, $data);
    }
}
