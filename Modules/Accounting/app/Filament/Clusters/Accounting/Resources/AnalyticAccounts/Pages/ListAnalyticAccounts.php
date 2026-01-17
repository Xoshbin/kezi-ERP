<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\AnalyticAccounts\AnalyticAccountResource;
use Modules\Foundation\Filament\Actions\DocsAction;

class ListAnalyticAccounts extends ListRecords
{
    protected static string $resource = AnalyticAccountResource::class;

    public function getTitle(): string
    {
        return __('accounting::analytic_account.pages.list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('analytic-configuration'),
        ];
    }
}
