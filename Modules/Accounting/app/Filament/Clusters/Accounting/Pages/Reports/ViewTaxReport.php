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
use Modules\Accounting\Filament\Clusters\Accounting\AccountingCluster;

class ViewTaxReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'accounting::filament.pages.reports.view-tax-report';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
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
        return __('accounting::reports.tax_report');
    }

    public function getTitle(): string
    {
        return __('accounting::reports.tax_report');
    }

    public function mount(): void
    {
        // Set default date range to current month
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.report_parameters'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('accounting::reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()->toDateString()),
                        DatePicker::make('endDate')
                            ->label(__('accounting::reports.end_date'))
                            ->required()
                            ->default(Carbon::now()->endOfMonth()->toDateString()),
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

        $user = Filament::auth()->user();
        if (! $user) {
            throw new Exception('User must be authenticated to view tax report');
        }

        $company = Company::findOrFail($user->company_id);
        $service = app(\Modules\Accounting\Services\Reports\TaxReportService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );

        // Convert DTO to array for Livewire compatibility
        $this->reportData = [
            'outputTaxLines' => $report->outputTaxLines->map(function ($line) {
                return [
                    'taxId' => $line->taxId,
                    'taxName' => $line->taxName,
                    'taxRate' => $line->taxRate,
                    'netAmount' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->netAmount),
                    'taxAmount' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->taxAmount),
                    'netAmountRaw' => $line->netAmount->getAmount()->toFloat(),
                    'taxAmountRaw' => $line->taxAmount->getAmount()->toFloat(),
                ];
            })->toArray(),
            'inputTaxLines' => $report->inputTaxLines->map(function ($line) {
                return [
                    'taxId' => $line->taxId,
                    'taxName' => $line->taxName,
                    'taxRate' => $line->taxRate,
                    'netAmount' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->netAmount),
                    'taxAmount' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->taxAmount),
                    'netAmountRaw' => $line->netAmount->getAmount()->toFloat(),
                    'taxAmountRaw' => $line->taxAmount->getAmount()->toFloat(),
                ];
            })->toArray(),
            'totalOutputTax' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalOutputTax),
            'totalInputTax' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalInputTax),
            'netTaxPayable' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->netTaxPayable),
            'netTaxPayableRaw' => $report->netTaxPayable->getAmount()->toFloat(),
            'companyName' => $company->name,
        ];

        $this->dispatch('report-generated');
    }
}
