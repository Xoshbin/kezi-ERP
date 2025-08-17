<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Livewire\Accounting\BankReconciliationMatcher;
use Filament\Resources\Pages\Page;

class BankReconciliation extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.bank-reconciliation';

    // We can pass the bank statement ID to the Livewire component
    public int $record;

    public function mount(int $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return __('bank_statement.reconcile_bank_statement');
    }

    public function getHeading(): string
    {
        return __('bank_statement.reconcile_bank_statement');
    }
}
