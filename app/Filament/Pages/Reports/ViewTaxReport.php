<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Services\Reports\TaxReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ViewTaxReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.reports.view-tax-report';

    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }

    protected static ?int $navigationSort = 6;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.tax_report');
    }

    public function getTitle(): string
    {
        return __('reports.tax_report');
    }

    public function mount(): void
    {
        // Set default date range to current month
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->endOfMonth()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('reports.report_parameters'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('reports.start_date'))
                            ->required()
                            ->default(Carbon::now()->startOfMonth()->toDateString()),
                        DatePicker::make('endDate')
                            ->label(__('reports.end_date'))
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

        $company = Company::find(Filament::auth()->user()->company_id);
        $service = app(TaxReportService::class);

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
                    'netAmount' => $line->netAmount->formatTo('en'),
                    'taxAmount' => $line->taxAmount->formatTo('en'),
                    'netAmountRaw' => $line->netAmount->getAmount()->toFloat(),
                    'taxAmountRaw' => $line->taxAmount->getAmount()->toFloat(),
                ];
            })->toArray(),
            'inputTaxLines' => $report->inputTaxLines->map(function ($line) {
                return [
                    'taxId' => $line->taxId,
                    'taxName' => $line->taxName,
                    'taxRate' => $line->taxRate,
                    'netAmount' => $line->netAmount->formatTo('en'),
                    'taxAmount' => $line->taxAmount->formatTo('en'),
                    'netAmountRaw' => $line->netAmount->getAmount()->toFloat(),
                    'taxAmountRaw' => $line->taxAmount->getAmount()->toFloat(),
                ];
            })->toArray(),
            'totalOutputTax' => $report->totalOutputTax->formatTo('en'),
            'totalInputTax' => $report->totalInputTax->formatTo('en'),
            'netTaxPayable' => $report->netTaxPayable->formatTo('en'),
            'netTaxPayableRaw' => $report->netTaxPayable->getAmount()->toFloat(),
            'companyName' => $company->name,
        ];

        $this->dispatch('report-generated');
    }
}
