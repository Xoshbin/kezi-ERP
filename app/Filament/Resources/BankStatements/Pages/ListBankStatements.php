<?php

namespace App\Filament\Resources\BankStatements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BankStatements\BankStatementResource;
use Filament\Actions;
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
        ];
    }
}
