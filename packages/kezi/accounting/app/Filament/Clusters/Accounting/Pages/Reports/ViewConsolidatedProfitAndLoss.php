<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Models\Company;
use BackedEnum;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Services\Reports\Consolidation\ConsolidatedProfitAndLossService;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Support\NumberFormatter;

class ViewConsolidatedProfitAndLoss extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'accounting::filament.pages.reports.view-consolidated-profit-and-loss';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public ?string $asOfDate = null; // Consolidated P&L usually needs a period too?
    // Wait, ConsolidatedP&LService expects $asOfDate, but P&L is a period report.
    // Let's check ConsolidatedProfitAndLossService::generate signature.
    // public function generate(Company $parentCompany, Carbon $asOfDate):
    // Inside it uses ConsolidatedTrialBalanceService::generate($parent, $asOfDate).
    // And inside Helper mapLines, it filters types.
    // Trial Balance is "As Of".
    // But P&L lines (Income/Expense) are accumulated over time.
    // In TrialBalanceService, it aggregates Journal Entries "WHERE entry_date <= asOfDate".
    // This implies "Since Beginning of Time" (or Fiscal Year start if filtered? No, TB usually is YTD).
    // So if TB is YTD, then P&L derived from TB is YTD (Start of Year to AsOfDate).
    // The standard ViewProfitAndLoss asks for Start/End Date.
    // My Consolidated Logic seems to be YTD based on TB.
    // I should only ask for "As Of Date" (End Date) and imply Start Date is Start of Fiscal Year.
    // Or I should fix Logic to support range if needed.
    // For now, I will ask for "As Of Date" (End Date) to match the service signature.

    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.consolidated_profit_and_loss');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.consolidated_profit_and_loss');
    }

    public function mount(): void
    {
        $this->asOfDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.report_parameters'))
                    ->schema([
                        DatePicker::make('asOfDate')
                            ->label(__('accounting::reports.as_of_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('profit-loss-report'),
            Action::make('generate')
                ->label(__('accounting::reports.generate_report'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'asOfDate' => ['required', 'date'],
        ]);

        $company = Filament::getTenant() ?? auth()->user()?->company;
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(ConsolidatedProfitAndLossService::class);
        $report = $service->generate($company, Carbon::parse($this->asOfDate));

        $formatter = fn ($money) => NumberFormatter::formatMoneyTo($money);
        $amount = fn ($money) => $money->getAmount()->toFloat();

        $this->reportData = [
            'incomeLines' => $report->incomeLines->map(fn ($line) => [
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => $formatter($line->balance),
                'balanceAmount' => $amount($line->balance),
            ])->toArray(),
            'expenseLines' => $report->expenseLines->map(fn ($line) => [
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => $formatter($line->balance),
                'balanceAmount' => $amount($line->balance),
            ])->toArray(),
            'totalIncome' => $formatter($report->totalIncome),
            'totalExpenses' => $formatter($report->totalExpenses),
            'netProfit' => $formatter($report->netProfit),
            'isNetLoss' => $report->netProfit->isNegative(),
            'periodLabel' => 'YTD (Start of Fiscal Year - '.Carbon::parse($this->asOfDate)->format('M j, Y').')',
        ];

        $this->dispatch('report-generated');
    }
}
