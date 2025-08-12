<?php

namespace App\Filament\Pages\Reports;

use App\Models\Company;
use App\Services\Reports\AgedReceivableService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

class ViewAgedReceivables extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.reports.view-aged-receivables';
    protected static ?string $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.reports');
    }
    protected static ?int $navigationSort = 4;

    public ?string $asOfDate = null;
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('reports.aged_receivables');
    }

    public function getTitle(): string|Htmlable
    {
        return __('reports.aged_receivables_report');
    }

    public function getHeading(): string|Htmlable
    {
        return __('reports.aged_receivables_report');
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
                            ->default(Carbon::now()->toDateString()),
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
        $service = app(AgedReceivableService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'reportLines' => $report->reportLines->map(fn($line) => [
                'partnerId' => $line->partnerId,
                'partnerName' => $line->partnerName,
                'current' => $line->current->formatTo(app()->getLocale()),
                'currentAmount' => $line->current->getAmount()->toFloat(),
                'bucket1_30' => $line->bucket1_30->formatTo(app()->getLocale()),
                'bucket1_30Amount' => $line->bucket1_30->getAmount()->toFloat(),
                'bucket31_60' => $line->bucket31_60->formatTo(app()->getLocale()),
                'bucket31_60Amount' => $line->bucket31_60->getAmount()->toFloat(),
                'bucket61_90' => $line->bucket61_90->formatTo(app()->getLocale()),
                'bucket61_90Amount' => $line->bucket61_90->getAmount()->toFloat(),
                'bucket90_plus' => $line->bucket90_plus->formatTo(app()->getLocale()),
                'bucket90_plusAmount' => $line->bucket90_plus->getAmount()->toFloat(),
                'totalDue' => $line->totalDue->formatTo(app()->getLocale()),
                'totalDueAmount' => $line->totalDue->getAmount()->toFloat(),
            ])->toArray(),
            'totalCurrent' => $report->totalCurrent->formatTo(app()->getLocale()),
            'totalCurrentAmount' => $report->totalCurrent->getAmount()->toFloat(),
            'totalBucket1_30' => $report->totalBucket1_30->formatTo(app()->getLocale()),
            'totalBucket1_30Amount' => $report->totalBucket1_30->getAmount()->toFloat(),
            'totalBucket31_60' => $report->totalBucket31_60->formatTo(app()->getLocale()),
            'totalBucket31_60Amount' => $report->totalBucket31_60->getAmount()->toFloat(),
            'totalBucket61_90' => $report->totalBucket61_90->formatTo(app()->getLocale()),
            'totalBucket61_90Amount' => $report->totalBucket61_90->getAmount()->toFloat(),
            'totalBucket90_plus' => $report->totalBucket90_plus->formatTo(app()->getLocale()),
            'totalBucket90_plusAmount' => $report->totalBucket90_plus->getAmount()->toFloat(),
            'grandTotalDue' => $report->grandTotalDue->formatTo(app()->getLocale()),
            'grandTotalDueAmount' => $report->grandTotalDue->getAmount()->toFloat(),
            'companyName' => $company->name,
            'asOfDate' => $this->asOfDate,
        ];
    }
}
