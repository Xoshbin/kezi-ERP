<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use Filament\Resources\Pages\Page;

class BankReconciliation extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.bank-reconciliation';

    // We can pass the bank statement ID to the Livewire component
    public int $record;

    public function mount(int $record): void
    {
        // Return 404 for non-existent records (keeps tests and UX logical)
        if (! \Modules\Accounting\Models\BankStatement::withoutGlobalScopes()->whereKey($record)->exists()) {
            abort(404);
        }

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

    /**
     * Check if the user can access this page.
     * Only allow access if reconciliation is enabled for the company.
     */
    public static function canAccess(array $parameters = []): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        return $tenant instanceof \App\Models\Company && $tenant->enable_reconciliation;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\App\Filament\Actions\DocsAction::make('bank-reconciliation'),
        ];
    }
}
