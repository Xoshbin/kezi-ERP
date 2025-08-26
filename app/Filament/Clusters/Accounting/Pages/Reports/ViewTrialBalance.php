<?php

namespace App\Filament\Clusters\Accounting\Pages\Reports;

use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use App\Support\NumberFormatter;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Illuminate\Contracts\Support\Htmlable;
use App\Services\Reports\TrialBalanceService;
use App\Filament\Clusters\Accounting\AccountingCluster;

class ViewTrialBalance extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';
    protected string $view = 'filament.pages.reports.view-trial-balance';
    protected static string | \UnitEnum | null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = AccountingCluster::class;

    public ?string $asOfDate = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.trial_balance');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.trial_balance_report');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.trial_balance_report');
    }

    public function mount(): void
    {
        $this->form->fill([
            'asOfDate' => Carbon::now()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('reports.report_parameters'))
                    ->schema([
                        DatePicker::make('asOfDate')
                            ->label(__('reports.as_of_date'))
                            ->required()
                            ->default(Carbon::now()),
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

        $company = Filament::getTenant();
        $service = app(TrialBalanceService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'companyName' => $company->name,
            'asOfDate' => $this->asOfDate,
            'reportLines' => $report->reportLines->map(fn($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'accountType' => $line->accountType->value,
                'debit' => NumberFormatter::formatMoneyTo($line->debit),
                'credit' => NumberFormatter::formatMoneyTo($line->credit),
                'debitAmount' => $line->debit->getAmount()->toFloat(),
                'creditAmount' => $line->credit->getAmount()->toFloat(),
            ])->toArray(),
            'totalDebit' => NumberFormatter::formatMoneyTo($report->totalDebit),
            'totalCredit' => NumberFormatter::formatMoneyTo($report->totalCredit),
            'totalDebitAmount' => $report->totalDebit->getAmount()->toFloat(),
            'totalCreditAmount' => $report->totalCredit->getAmount()->toFloat(),
            'isBalanced' => $report->isBalanced,
        ];
    }
}
