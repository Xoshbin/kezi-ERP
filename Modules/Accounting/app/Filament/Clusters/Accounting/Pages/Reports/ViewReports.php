<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Filament\Clusters\Accounting\AccountingCluster;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected string $view = 'filament.pages.reports.view-reports';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationLabel(): string
    {
        return __('reports.reports');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.financial_reports');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.financial_reports');
    }

    public function getSubheading(): ?string
    {
        return __('reports.select_report_description');
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
                'title' => __('reports.financial_statements'),
                'description' => __('reports.financial_statements_description'),
                'icon' => 'heroicon-o-chart-bar',
                'reports' => [
                    [
                        'name' => __('reports.profit_and_loss'),
                        'description' => __('reports.profit_and_loss_description'),
                        'icon' => 'heroicon-o-chart-bar',
                        'url' => $baseUrl.'/view-profit-and-loss',
                        'button_text' => __('reports.view_profit_loss'),
                    ],
                    [
                        'name' => __('reports.balance_sheet'),
                        'description' => __('reports.balance_sheet_description'),
                        'icon' => 'heroicon-o-scale',
                        'url' => $baseUrl.'/view-balance-sheet',
                        'button_text' => __('reports.view_balance_sheet'),
                    ],
                    [
                        'name' => __('reports.trial_balance'),
                        'description' => __('reports.trial_balance_description'),
                        'icon' => 'heroicon-o-scale',
                        'url' => $baseUrl.'/view-trial-balance',
                        'button_text' => __('reports.view_trial_balance'),
                    ],
                ],
            ],
            'detailed_reports' => [
                'title' => __('reports.detailed_reports'),
                'description' => __('reports.detailed_reports_description'),
                'icon' => 'heroicon-o-document-text',
                'reports' => [
                    [
                        'name' => __('reports.general_ledger'),
                        'description' => __('reports.general_ledger_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-general-ledger',
                        'button_text' => __('reports.view_general_ledger'),
                    ],
                    [
                        'name' => __('reports.partner_ledger'),
                        'description' => __('reports.partner_ledger_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-partner-ledger',
                        'button_text' => __('reports.view_partner_ledger'),
                    ],
                ],
            ],
            'aging_reports' => [
                'title' => __('reports.aging_reports'),
                'description' => __('reports.aging_reports_description'),
                'icon' => 'heroicon-o-clock',
                'reports' => [
                    [
                        'name' => __('reports.aged_receivables'),
                        'description' => __('reports.aged_receivables_description'),
                        'icon' => 'heroicon-o-clock',
                        'url' => $baseUrl.'/view-aged-receivables',
                        'button_text' => __('reports.view_aged_receivables'),
                    ],
                    [
                        'name' => __('reports.aged_payables'),
                        'description' => __('reports.aged_payables_description'),
                        'icon' => 'heroicon-o-clock',
                        'url' => $baseUrl.'/view-aged-payables',
                        'button_text' => __('reports.view_aged_payables'),
                    ],
                ],
            ],
            'tax_reports' => [
                'title' => __('reports.tax_reports'),
                'description' => __('reports.tax_reports_description'),
                'icon' => 'heroicon-o-document-text',
                'reports' => [
                    [
                        'name' => __('reports.tax_report'),
                        'description' => __('reports.tax_report_description'),
                        'icon' => 'heroicon-o-document-text',
                        'url' => $baseUrl.'/view-tax-report',
                        'button_text' => __('reports.view_tax_report'),
                    ],
                ],
            ],
        ];
    }
}
