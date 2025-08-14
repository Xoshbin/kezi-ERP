<?php

namespace App\Filament\Pages\Reports;

use App\Services\Reports\BalanceSheetService;
use App\Support\NumberFormatter;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;


class ViewBalanceSheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static string $view = 'filament.pages.reports.view-balance-sheet';
    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 2;

    public ?string $asOfDate = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.balance_sheet');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.balance_sheet');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.balance_sheet');
    }

    public function mount(): void
    {
        // Set default date to end of current month
        $this->asOfDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('reports.as_of_date'))
                    ->schema([
                        DatePicker::make('asOfDate')
                            ->label(__('reports.as_of_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()),
                    ]),
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
            'asOfDate' => 'required|date',
        ]);

        $company = \Filament\Facades\Filament::getTenant();
        $service = app(BalanceSheetService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'assetLines' => $report->assetLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'liabilityLines' => $report->liabilityLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'equityLines' => $report->equityLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'balance' => NumberFormatter::formatMoneyTo($line->balance),
                'balanceAmount' => $line->balance->getAmount()->toFloat(),
            ])->toArray(),
            'totalAssets' => NumberFormatter::formatMoneyTo($report->totalAssets),
            'totalLiabilities' => NumberFormatter::formatMoneyTo($report->totalLiabilities),
            'retainedEarnings' => NumberFormatter::formatMoneyTo($report->retainedEarnings),
            'currentYearEarnings' => NumberFormatter::formatMoneyTo($report->currentYearEarnings),
            'currentYearEarningsAmount' => $report->currentYearEarnings->getAmount()->toFloat(),
            'isCurrentYearLoss' => $report->currentYearEarnings->isNegative(),
            'totalEquity' => NumberFormatter::formatMoneyTo($report->totalEquity),
            'totalLiabilitiesAndEquity' => NumberFormatter::formatMoneyTo($report->totalLiabilitiesAndEquity),
        ];

        $this->dispatch('report-generated');
    }
}
