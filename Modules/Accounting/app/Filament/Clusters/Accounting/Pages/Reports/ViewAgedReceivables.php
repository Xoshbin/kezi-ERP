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

class ViewAgedReceivables extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'accounting::filament.pages.reports.view-aged-receivables';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $cluster = AccountingCluster::class;

    public ?string $asOfDate = null;

    /** @var array<string, mixed>|null */
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
        // Use explicit schema getter to satisfy static analysis
        $this->getSchema('form')?->fill([
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
            'asOfDate' => ['required', 'date'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(\Modules\Accounting\Services\Reports\AgedReceivableService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'reportLines' => $report->reportLines->map(fn($line) => [
                'partnerId' => $line->partnerId,
                'partnerName' => $line->partnerName,
                'current' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->current),
                'currentAmount' => $line->current->getAmount()->toFloat(),
                'bucket1_30' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket1_30),
                'bucket1_30Amount' => $line->bucket1_30->getAmount()->toFloat(),
                'bucket31_60' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket31_60),
                'bucket31_60Amount' => $line->bucket31_60->getAmount()->toFloat(),
                'bucket61_90' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket61_90),
                'bucket61_90Amount' => $line->bucket61_90->getAmount()->toFloat(),
                'bucket90_plus' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket90_plus),
                'bucket90_plusAmount' => $line->bucket90_plus->getAmount()->toFloat(),
                'totalDue' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($line->totalDue),
                'totalDueAmount' => $line->totalDue->getAmount()->toFloat(),
            ])->toArray(),
            'totalCurrent' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalCurrent),
            'totalCurrentAmount' => $report->totalCurrent->getAmount()->toFloat(),
            'totalBucket1_30' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket1_30),
            'totalBucket1_30Amount' => $report->totalBucket1_30->getAmount()->toFloat(),
            'totalBucket31_60' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket31_60),
            'totalBucket31_60Amount' => $report->totalBucket31_60->getAmount()->toFloat(),
            'totalBucket61_90' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket61_90),
            'totalBucket61_90Amount' => $report->totalBucket61_90->getAmount()->toFloat(),
            'totalBucket90_plus' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket90_plus),
            'totalBucket90_plusAmount' => $report->totalBucket90_plus->getAmount()->toFloat(),
            'grandTotalDue' => \Modules\Foundation\Support\NumberFormatter::formatMoneyTo($report->grandTotalDue),
            'grandTotalDueAmount' => $report->grandTotalDue->getAmount()->toFloat(),
            'companyName' => (string) ($company->name ?? ''),
            'asOfDate' => $this->asOfDate,
        ];
    }
}
