<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnalyticAccount extends EditRecord
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('analytic_account.pages.edit.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
