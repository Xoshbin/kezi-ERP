<?php

namespace App\Filament\Resources\AnalyticAccounts\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AnalyticAccounts\AnalyticAccountResource;
use Filament\Actions;
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
