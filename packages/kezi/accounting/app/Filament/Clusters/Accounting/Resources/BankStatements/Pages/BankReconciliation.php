<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use App\Models\Company;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Foundation\Filament\Actions\DocsAction;

class BankReconciliation extends Page
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'accounting::filament.resources.bank-statement-resource.pages.bank-reconciliation';

    // We can pass the bank statement ID to the Livewire component
    public int $record;

    public function mount(int $record): void
    {
        // Return 404 for non-existent records (keeps tests and UX logical)
        if (! BankStatement::withoutGlobalScopes()->whereKey($record)->exists()) {
            abort(404);
        }

        $this->record = $record;
    }

    public function getTitle(): string
    {
        return __('accounting::bank_statement.reconcile_bank_statement');
    }

    public function getHeading(): string
    {
        return __('accounting::bank_statement.reconcile_bank_statement');
    }

    /**
     * Check if the user can access this page.
     * Only allow access if reconciliation is enabled for the company.
     */
    public static function canAccess(array $parameters = []): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company && $tenant->enable_reconciliation;
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('bank-reconciliation'),
        ];
    }
}
