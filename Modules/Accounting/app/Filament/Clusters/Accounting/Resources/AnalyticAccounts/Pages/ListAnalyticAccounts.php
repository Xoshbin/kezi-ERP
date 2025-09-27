<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use App\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticAccounts extends ListRecords
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('analytic_account.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
