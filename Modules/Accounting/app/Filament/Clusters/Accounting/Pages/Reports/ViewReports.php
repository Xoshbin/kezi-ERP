<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;

class ViewReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'accounting::filament.pages.reports.view-reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.financial_reports');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.financial_reports');
    }

    public function getSubheading(): ?string
    {
        return __('accounting::reports.select_report_description');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getReportCategories(): array
    {
        $tenant = Filament::getTenant();
        $tenantKey = $tenant ? $tenant->getRouteKey() : request()->route('tenant');
        $baseUrl = url("/jmeryar/{$tenantKey}/accounting");

        return [
            'financial_statements' => [
                'title' => __('accounting::reports.financial_statements'),
                'description' => __('accounting::reports.financial_statements_description'),
                'icon' => 'heroicon-o-chart-bar',
                'reports' => [
                    [
                        'name' => __('accounting::reports.profit_and_loss'),
                        'description' => __('accounting::reports.profit_and_loss_description'),
                        'icon' => 'heroicon-o-chart-bar',
                        'url' => $baseUrl.'/view-profit-and-loss',
                        'button_text' => __('accounting::reports.view_profit_loss'),
                    ],
                    [
                        'name' => __('accounting::reports.balance_sheet'),
                        'description' => __('accounting::reports.balance_sheet_description'),
                        'icon' => 'heroicon-o-scale',
                        'url' => $baseUrl.'/view-balance-sheet',
                        'button_text' => __('accounting::reports.view_balance_sheet'),
                    ],
                    [
                        'name' => __('accounting::reports.trial_balance'),
                        'description' => __('accounting::reports.trial_balance_description'),
                        'icon' => 'heroicon-o-scale',
                        'url' => $baseUrl.'/view-trial-balance',
                        'button_text' => __('accounting::reports.view_trial_balance'),
                    ],
                    [
                        'name' => __('accounting::reports.cash_flow_statement'),
                        'description' => __('accounting::reports.cash_flow_statement_description'),
                        'icon' => 'heroicon-o-banknotes',
                        'url' => $baseUrl.'/view-cash-flow-statement',
                        'button_text' => __('accounting::reports.view_cash_flow_statement'),
                    ],
                ],
            ],
            'detailed_reports' => [
                'title' => __('accounting::reports.detailed_reports'),
                'description' => __('accounting::reports.detailed_reports_description'),
                'icon' => 'heroicon-o-document-text',
                'reports' => [
                    [
                        'name' => __('accounting::reports.general_ledger'),
                        'description' => __('accounting::reports.general_ledger_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-general-ledger',
                        'button_text' => __('accounting::reports.view_general_ledger'),
                    ],
                    [
                        'name' => __('accounting::reports.partner_ledger'),
                        'description' => __('accounting::reports.partner_ledger_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-partner-ledger',
                        'button_text' => __('accounting::reports.view_partner_ledger'),
                    ],
                ],
            ],
            'aging_reports' => [
                'title' => __('accounting::reports.aging_reports'),
                'description' => __('accounting::reports.aging_reports_description'),
                'icon' => 'heroicon-o-clock',
                'reports' => [
                    [
                        'name' => __('accounting::reports.aged_receivables'),
                        'description' => __('accounting::reports.aged_receivables_description'),
                        'icon' => 'heroicon-o-clock',
                        'url' => $baseUrl.'/view-aged-receivables',
                        'button_text' => __('accounting::reports.view_aged_receivables'),
                    ],
                    [
                        'name' => __('accounting::reports.aged_payables'),
                        'description' => __('accounting::reports.aged_payables_description'),
                        'icon' => 'heroicon-o-clock',
                        'url' => $baseUrl.'/view-aged-payables',
                        'button_text' => __('accounting::reports.view_aged_payables'),
                    ],
                ],
            ],
            'tax_reports' => [
                'title' => __('accounting::reports.tax_reports'),
                'description' => __('accounting::reports.tax_reports_description'),
                'icon' => 'heroicon-o-document-text',
                'reports' => [
                    [
                        'name' => __('accounting::reports.tax_report'),
                        'description' => __('accounting::reports.tax_report_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-tax-report',
                        'button_text' => __('accounting::reports.view_tax_report'),
                    ],
                ],
            ],
            'consolidated_reports' => [
                'title' => __('accounting::reports.consolidated_reports'),
                'description' => __('accounting::reports.consolidated_reports_description'),
                'icon' => 'heroicon-o-globe-alt',
                'reports' => [
                    [
                        'name' => __('accounting::reports.consolidated_profit_and_loss'),
                        'description' => __('accounting::reports.consolidated_profit_and_loss_description'),
                        'icon' => 'heroicon-o-chart-bar',
                        'url' => $baseUrl.'/view-consolidated-profit-and-loss',
                        'button_text' => __('accounting::reports.view_consolidated_profit_and_loss'),
                    ],
                ],
            ],
        ];
    }
}
