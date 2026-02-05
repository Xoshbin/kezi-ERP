<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ListBankStatements extends ListRecords
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('accounting::bank_statement.list_bank_statements');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('bank-statements'),
        ];
    }
}
