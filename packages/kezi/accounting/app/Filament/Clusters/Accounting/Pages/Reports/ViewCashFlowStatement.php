<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Models\Company;
use BackedEnum;
use Carbon\Carbon;
use Exception;
use \Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Accounting\Services\Reports\CashFlowStatementService;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Foundation\Support\NumberFormatter;

class ViewCashFlowStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected string $view = 'accounting::filament.pages.reports.view-cash-flow-statement';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.cash_flow_statement');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.cash_flow_statement');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.cash_flow_statement');
    }

    public function mount(): void
    {
        // Set default dates to current fiscal year (assuming Jan 1st)
        $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.date_range'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('accounting::reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfYear()),
                        DatePicker::make('endDate')
                            ->label(__('accounting::reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('cash-flow-statement'),
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
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(CashFlowStatementService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'operatingLines' => $report->operatingLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'description' => $line->description,
                'amount' => NumberFormatter::formatMoneyTo($line->amount),
                'amountValue' => $line->amount->getAmount()->toFloat(),
                'isNegative' => $line->amount->isNegative(),
            ])->toArray(),
            'totalOperating' => NumberFormatter::formatMoneyTo($report->totalOperating),
            'totalOperatingValue' => $report->totalOperating->getAmount()->toFloat(),
            'isTotalOperatingNegative' => $report->totalOperating->isNegative(),

            'investingLines' => $report->investingLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'description' => $line->description,
                'amount' => NumberFormatter::formatMoneyTo($line->amount),
                'amountValue' => $line->amount->getAmount()->toFloat(),
                'isNegative' => $line->amount->isNegative(),
            ])->toArray(),
            'totalInvesting' => NumberFormatter::formatMoneyTo($report->totalInvesting),
            'totalInvestingValue' => $report->totalInvesting->getAmount()->toFloat(),
            'isTotalInvestingNegative' => $report->totalInvesting->isNegative(),

            'financingLines' => $report->financingLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'description' => $line->description,
                'amount' => NumberFormatter::formatMoneyTo($line->amount),
                'amountValue' => $line->amount->getAmount()->toFloat(),
                'isNegative' => $line->amount->isNegative(),
            ])->toArray(),
            'totalFinancing' => NumberFormatter::formatMoneyTo($report->totalFinancing),
            'totalFinancingValue' => $report->totalFinancing->getAmount()->toFloat(),
            'isTotalFinancingNegative' => $report->totalFinancing->isNegative(),

            'netChangeInCash' => NumberFormatter::formatMoneyTo($report->netChangeInCash),
            'netChangeInCashValue' => $report->netChangeInCash->getAmount()->toFloat(),
            'isNetChangeNegative' => $report->netChangeInCash->isNegative(),

            'beginningCash' => NumberFormatter::formatMoneyTo($report->beginningCash),
            'endingCash' => NumberFormatter::formatMoneyTo($report->endingCash),
        ];

        $this->dispatch('report-generated');
    }
}
