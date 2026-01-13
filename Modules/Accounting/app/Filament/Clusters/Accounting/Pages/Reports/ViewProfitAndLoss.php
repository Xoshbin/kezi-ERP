<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports;

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
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;

class ViewProfitAndLoss extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected string $view = 'accounting::filament.pages.reports.view-profit-and-loss';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __("navigation.groups.reports");
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.profit_and_loss');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.profit_and_loss_statement');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.profit_and_loss_statement');
    }

    public function mount(): void
    {
        // Set default date range to current month
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
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
                            ->default(Carbon::now()->startOfMonth()),
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

        $company = Filament::getTenant() ?? auth()->user()?->company;
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(\Modules\Accounting\Services\Reports\ProfitAndLossStatementService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'revenueLines' => $report->revenueLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'expenseLines' => $report->expenseLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'totalRevenue' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalRevenue),
            'totalExpenses' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalExpenses),
            'netIncome' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->netIncome),
            'netIncomeAmount' => $report->netIncome->getAmount()->toFloat(),
            'isNetLoss' => $report->netIncome->isNegative(),
        ];

        $this->dispatch('report-generated');
    }
}
