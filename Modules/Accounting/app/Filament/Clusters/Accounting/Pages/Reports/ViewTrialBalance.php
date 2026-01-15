<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use App\Models\Company;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Modules\Foundation\Filament\Actions\DocsAction;

class ViewTrialBalance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected string $view = 'accounting::filament.pages.reports.view-trial-balance';

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
        return __('accounting::reports.trial_balance');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.trial_balance_report');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.trial_balance_report');
    }

    public function mount(): void
    {
        // Use explicit schema getter to satisfy static analysis
        $this->getSchema('form')?->fill([
            'asOfDate' => Carbon::now()->toDateString(),
        ]);
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
                            ->default(Carbon::now()),
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
            DocsAction::make('trial-balance-report'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'asOfDate' => ['required', 'date'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            return;
        }
        $service = app(\Modules\Accounting\Services\Reports\TrialBalanceService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'companyName' => $company->name,
            'asOfDate' => $this->asOfDate,
            'reportLines' => $report->reportLines->map(fn ($line) => [
                'accountId' => $line->accountId,
                'accountCode' => $line->accountCode,
                'accountName' => $line->accountName,
                'accountType' => $line->accountType->value,
                'debit' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->debit),
                'credit' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->credit),
                'debitAmount' => $line->debit->getAmount()->toFloat(),
                'creditAmount' => $line->credit->getAmount()->toFloat(),
            ])->toArray(),
            'totalDebit' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalDebit),
            'totalCredit' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalCredit),
            'totalDebitAmount' => $report->totalDebit->getAmount()->toFloat(),
            'totalCreditAmount' => $report->totalCredit->getAmount()->toFloat(),
            'isBalanced' => $report->isBalanced,
        ];
    }
}
