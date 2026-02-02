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

class ViewBalanceSheet extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected string $view = 'accounting::filament.pages.reports.view-balance-sheet';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public ?string $asOfDate = null;

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.balance_sheet');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.balance_sheet');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.balance_sheet');
    }

    public function mount(): void
    {
        // Set default date to end of current month
        $this->asOfDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.as_of_date'))
                    ->schema([
                        DatePicker::make('asOfDate')
                            ->label(__('accounting::reports.as_of_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ]),
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
            \Kezi\Foundation\Filament\Actions\DocsAction::make('balance-sheet-report'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'asOfDate' => ['required', 'date'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(\Kezi\Accounting\Services\Reports\BalanceSheetService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'assetLines' => $report->assetLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'liabilityLines' => $report->liabilityLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'equityLines' => $report->equityLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'totalAssets' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalAssets),
            'totalLiabilities' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalLiabilities),
            'retainedEarnings' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->retainedEarnings),
            'currentYearEarnings' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->currentYearEarnings),
            'currentYearEarningsAmount' => $report->currentYearEarnings->getAmount()->toFloat(),
            'isCurrentYearLoss' => $report->currentYearEarnings->isNegative(),
            'totalEquity' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalEquity),
            'totalLiabilitiesAndEquity' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalLiabilitiesAndEquity),
        ];

        $this->dispatch('report-generated');
    }
}
