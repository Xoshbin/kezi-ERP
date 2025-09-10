<?php

namespace App\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use App\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use App\Filament\Actions\DocsAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankStatements extends ListRecords
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('bank_statement.list_bank_statements');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('bank-statements'),
        ];
    }
}
