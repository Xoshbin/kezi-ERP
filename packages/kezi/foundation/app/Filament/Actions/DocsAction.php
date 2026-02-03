<?php

namespace Kezi\Foundation\Filament\Actions;

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
            ->url(route('pertuk.docs.version.show', [
                'version' => \Xoshbin\Pertuk\Services\DocumentationService::make()->getVersion() ?? 'v1.0',
                'locale' => app()->getLocale(),
                'slug' => $fullSlug,
            ]))
            ->openUrlInNewTab();
    }

    /**
     * Map short slugs to their full documentation paths
     */
    private static function mapSlugToDocumentationPath(string $slug): string
    {
        $mapping = [
            'payments' => 'how-to/payments',
            'loan-agreements' => 'how-to/loan-agreements',
            'customer-invoices' => 'how-to/customer-invoices',
            'vendor-bills' => 'how-to/vendor-bills',
            'bank-statements' => 'how-to/bank-statements',
            'bank-reconciliation' => 'how-to/bank-reconciliation',

            'opening-balances' => 'how-to/recording-opening-balances',
            'payment-terms-guide' => 'how-to/payment-terms-guide',
            'stock-picking' => 'how-to/stock-picking',
            'trial-balance-report' => 'how-to/generating-financial-reports',
            'balance-sheet-report' => 'how-to/generating-financial-reports',
            'profit-loss-report' => 'how-to/generating-financial-reports',
            'cash-flow-statement' => 'how-to/generating-financial-reports',
            'general-ledger-report' => 'how-to/generating-financial-reports',
            'aged-receivables-report' => 'how-to/aged-receivables-report',
            'aged-payables-report' => 'how-to/aged-payables-report',
            'partner-ledger-report' => 'how-to/partner-ledger-report',
            'tax-report' => 'how-to/tax-report',
            'journal-entry-flow' => 'explanation/automatic-journal-flow',
            'payroll-processing' => 'how-to/process-payroll',
            'processing-your-first-payroll' => 'tutorials/processing-your-first-payroll',
            'payroll-workflows' => 'explanation/payroll-workflows',
            'payroll-statuses-and-accounting' => 'reference/payroll-statuses-and-accounting',
            'analytic-report' => 'how-to/analytic-report',
            'cheque-management' => 'how-to/cheque-management',
            'employee-management' => 'how-to/manage-employees',
            'onboarding-your-first-employee' => 'tutorials/onboarding-your-first-employee',
            'employee-records' => 'explanation/employee-records',
            'employee-fields-and-statuses' => 'reference/employee-fields-and-statuses',
            'leave-management' => 'how-to/manage-leave',
            'leave-and-attendance-concepts' => 'explanation/leave-and-attendance-concepts',
            'leave-types-and-statuses' => 'reference/leave-types-and-statuses',
            'expense-reports' => 'how-to/expense-reports',
            'department-position-config' => 'how-to/department-position-config',
            'quality-checks' => 'how-to/quality-checks',
            'quality-alerts' => 'how-to/quality-alerts',
            'quality-points' => 'how-to/quality-points',
            'budget-management' => 'how-to/budget-management',
            'analytic-configuration' => 'how-to/analytic-configuration',
            'adjustment-documents' => 'how-to/adjustment-documents',
            'account-groups' => 'how-to/account-groups',
            'credit-notes' => 'how-to/credit-notes',
            'debit-notes' => 'how-to/debit-notes',
            'currency-revaluation' => 'how-to/currency-revaluation',
            'deferred-items' => 'how-to/deferred-items',
            'dunning-levels' => 'how-to/dunning-levels',
            'recurring-templates' => 'how-to/recurring-templates',
            'lock-dates' => 'how-to/lock-dates',
            'fixed-assets' => 'how-to/fixed-assets',
            'journal-entries' => 'how-to/journal-entries',
            'tax-management' => 'how-to/tax-management',
            'fiscal-positions' => 'how-to/fiscal-positions',
            'understanding-inventory-ins-and-outs' => 'explanation/understanding-inventory-ins-and-outs',
            'landed-costs' => 'how-to/landed-costs',
            'bill-of-materials' => 'how-to/bill-of-materials',
            'manufacturing-orders' => 'how-to/manufacturing-orders',
            'project-management' => 'how-to/project-management',
            'project-budgeting' => 'how-to/project-budgeting',
            'timesheet-tracking' => 'how-to/timesheet-tracking',
            'lot-tracking' => 'how-to/lot-tracking',
            'serial-number-tracking' => 'how-to/serial-number-tracking',
            'inter-warehouse-transfers' => 'how-to/inter-warehouse-transfers',
            'stock-movements' => 'how-to/stock-movements',
            'reordering-rules' => 'how-to/reordering-rules',
            'inventory-management' => 'how-to/managing-stock', // Refactored from inventory-management
            'managing-stock' => 'how-to/managing-stock',
            'your-first-warehouse-setup' => 'tutorials/your-first-warehouse-setup',
            'inventory-concepts' => 'explanation/inventory-concepts',
            'inventory-architecture' => 'explanation/inventory-architecture',
            'inventory-fields' => 'reference/inventory-fields',
            'understanding-fiscal-years' => 'explanation/understanding-fiscal-years',
            'understanding-cash-advances' => 'explanation/understanding-cash-advances',
            'understanding-petty-cash' => 'explanation/understanding-petty-cash',
            'incoterms' => 'explanation/incoterms',
            'getting-started' => 'tutorials/getting-started',
            'understanding-letter-of-credit' => 'explanation/understanding-letter-of-credit',
            'understanding-withholding-tax' => 'explanation/understanding-withholding-tax',
            'scrap-management' => 'how-to/scrap-management',
            'inventory-adjustments' => 'how-to/inventory-adjustments',
            'inventory-reports' => 'how-to/inventory-reports',
            'understanding-sales-quotes' => 'explanation/understanding-sales-quotes',
            'understanding-purchase-orders' => 'explanation/understanding-purchase-orders',
            'understanding-project-tasks' => 'explanation/understanding-project-tasks',
            'understanding-work-centers' => 'explanation/understanding-work-centers',
            'understanding-work-orders' => 'explanation/understanding-work-orders',
            'attendance-management' => 'how-to/track-attendance',
            'employment-contracts' => 'how-to/employment-contracts',
            'understanding-reversals' => 'explanation/understanding-reversals',
            'understanding-project-invoicing' => 'explanation/understanding-project-invoicing',
            'understanding-vendor-management' => 'explanation/understanding-vendor-management',
            'understanding-advanced-payments' => 'explanation/understanding-advanced-payments',
            'understanding-production-planning' => 'explanation/understanding-production-planning',
            'understanding-accounts' => 'explanation/understanding-accounts',
            'understanding-audit-logs' => 'explanation/understanding-audit-logs',
            'understanding-financial-reports' => 'explanation/financial-reporting-concepts',
            'understanding-currencies' => 'explanation/understanding-currencies',
            'understanding-numbering-settings' => 'explanation/understanding-numbering-settings',
            'understanding-pdf-settings' => 'explanation/understanding-pdf-settings',
            'understanding-rfq' => 'explanation/understanding-rfq',
            'understanding-sales-orders' => 'explanation/understanding-sales-orders',
            'product-attribute' => 'reference/product-attribute',
            'product-category' => 'reference/product-category',
            'filament-enum-usage-examples' => 'reference/filament-enum-usage-examples',
            'number-formatting' => 'reference/number-formatting',
            'tax-reporting-plugins' => 'reference/tax-reporting-plugins',
            'translation-scanner' => 'how-to/translation-scanner',
        ];

        return $mapping[$slug] ?? $slug;
    }
}
