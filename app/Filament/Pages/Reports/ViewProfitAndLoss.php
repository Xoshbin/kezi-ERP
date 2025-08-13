<?php

namespace App\Filament\Pages\Reports;

use App\Services\Reports\ProfitAndLossStatementService;
use App\Support\NumberFormatter;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

class ViewProfitAndLoss extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.reports.view-profit-and-loss';
    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 1;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.profit_and_loss');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.profit_and_loss_statement');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.profit_and_loss_statement');
    }

    public function mount(): void
    {
        // Set default date range to current month
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('reports.date_range'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label(__('reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label(__('reports.generate_report'))
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('generateReport'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $company = Filament::getTenant() ?? auth()->user()?->company;
        $service = app(ProfitAndLossStatementService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'revenueLines' => $report->revenueLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'expenseLines' => $report->expenseLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'totalRevenue' => NumberFormatter::formatMoneyTo($report->totalRevenue),
            'totalExpenses' => NumberFormatter::formatMoneyTo($report->totalExpenses),
            'netIncome' => NumberFormatter::formatMoneyTo($report->netIncome),
            'netIncomeAmount' => $report->netIncome->getAmount()->toFloat(),
            'isNetLoss' => $report->netIncome->isNegative(),
        ];

        $this->dispatch('report-generated');
    }
}
