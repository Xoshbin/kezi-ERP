<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Services\Reports\TrialBalanceService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

class ViewTrialBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static string $view = 'filament.pages.reports.view-trial-balance';
    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 3;

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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

        $company = Company::find(Filament::auth()->user()->company_id);
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
                'debit' => $line->debit->formatTo(app()->getLocale()),
                'credit' => $line->credit->formatTo(app()->getLocale()),
                'debitAmount' => $line->debit->getAmount()->toFloat(),
                'creditAmount' => $line->credit->getAmount()->toFloat(),
            ])->toArray(),
            'totalDebit' => $report->totalDebit->formatTo(app()->getLocale()),
            'totalCredit' => $report->totalCredit->formatTo(app()->getLocale()),
            'totalDebitAmount' => $report->totalDebit->getAmount()->toFloat(),
            'totalCreditAmount' => $report->totalCredit->getAmount()->toFloat(),
            'isBalanced' => $report->isBalanced,
        ];
    }
}
