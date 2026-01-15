<?php

namespace Modules\Foundation\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Str;

class DocsAction
{
    public static function make(string $slug): Action
    {
        // Map short slugs to full documentation paths
        $fullSlug = self::mapSlugToDocumentationPath($slug);

        // Generate a human-readable title from the original slug
        $title = Str::of($slug)
            ->replace('-', ' ')
            ->title()
            ->append(' '.__('foundation::messages.guide'));

        // Generate the action name (slug + '_docs' to match test expectations)
        $actionName = $slug.'_docs';

        return Action::make($actionName)
            ->label($title)
            ->icon('heroicon-o-book-open')
            ->color('info')
            ->url(route('docs.show', ['slug' => $fullSlug]))
            ->openUrlInNewTab();
    }

    /**
     * Map short slugs to their full documentation paths
     */
    private static function mapSlugToDocumentationPath(string $slug): string
    {
        $mapping = [
            'payments' => 'User Guide/payments',
            'loan-agreements' => 'User Guide/loan-agreements',
            'customer-invoices' => 'User Guide/customer-invoices',
            'vendor-bills' => 'User Guide/vendor-bills',
            'bank-statements' => 'User Guide/bank-statements',
            'bank-reconciliation' => 'User Guide/bank-reconciliation',
            'stock-management' => 'User Guide/stock-management',
            'opening-balances' => 'User Guide/opening-balances',
            'payment-terms-guide' => 'User Guide/payment-terms-guide',
            'receipt-payment-vouchers' => 'User Guide/receipt-payment-vouchers',
            'stock-picking' => 'User Guide/stock-picking',
            'trial-balance-report' => 'User Guide/trial-balance-report',
            'balance-sheet-report' => 'User Guide/balance-sheet-report',
            'profit-loss-report' => 'User Guide/profit-loss-report',
            'cash-flow-statement' => 'User Guide/cash-flow-statement',
            'general-ledger-report' => 'User Guide/general-ledger-report',
            'aged-receivables-report' => 'User Guide/aged-receivables-report',
            'aged-payables-report' => 'User Guide/aged-payables-report',
            'partner-ledger-report' => 'User Guide/partner-ledger-report',
            'tax-report' => 'User Guide/tax-report',
            'journal-entry-flow' => 'Developers/journal_entry_flow_report',
            'payroll-processing' => 'User Guide/payroll-processing',
            'analytic-report' => 'User Guide/analytic-report',
            'cheque-management' => 'User Guide/cheque-management',
            'employee-management' => 'User Guide/employee-management',
            'leave-management' => 'User Guide/leave-management',
            'expense-reports' => 'User Guide/expense-reports',
            'department-position-config' => 'User Guide/department-position-config',
            'quality-checks' => 'User Guide/quality-checks',
            'quality-alerts' => 'User Guide/quality-alerts',
            'quality-points' => 'User Guide/quality-points',
            'budget-management' => 'User Guide/budget-management',
        ];

        return $mapping[$slug] ?? $slug;
    }
}
