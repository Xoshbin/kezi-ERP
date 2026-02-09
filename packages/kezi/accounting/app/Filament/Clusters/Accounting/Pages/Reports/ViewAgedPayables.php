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

class ViewAgedPayables extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'accounting::filament.pages.reports.view-aged-payables';

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
        return __('accounting::reports.aged_payables');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.aged_payables_report');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.aged_payables_report');
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
                            ->default(Carbon::now()->toDateString()),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('aged-payables-report'),
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
            'asOfDate' => ['required', 'date'],
        ]);

        $company = Filament::getTenant();
        if (! $company instanceof Company) {
            throw new Exception('Company not found');
        }

        $service = app(\Kezi\Accounting\Services\Reports\AgedPayableService::class);

        $report = $service->generate(
            $company,
            Carbon::parse($this->asOfDate)
        );

        // Convert to array format that Livewire can handle
        $this->reportData = [
            'reportLines' => $report->reportLines->map(fn ($line) => [
                'partnerId' => $line->partnerId,
                'partnerName' => $line->partnerName,
                'current' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->current),
                'hasCurrent' => $line->current->isPositive(),
                'bucket1_30' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket1_30),
                'hasBucket1_30' => $line->bucket1_30->isPositive(),
                'bucket31_60' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket31_60),
                'hasBucket31_60' => $line->bucket31_60->isPositive(),
                'bucket61_90' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket61_90),
                'hasBucket61_90' => $line->bucket61_90->isPositive(),
                'bucket90_plus' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->bucket90_plus),
                'hasBucket90_plus' => $line->bucket90_plus->isPositive(),
                'totalDue' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($line->totalDue),
            ])->toArray(),
            'totalCurrent' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalCurrent),
            'totalBucket1_30' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket1_30),
            'totalBucket31_60' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket31_60),
            'totalBucket61_90' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket61_90),
            'totalBucket90_plus' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->totalBucket90_plus),
            'grandTotalDue' => \Kezi\Foundation\Support\NumberFormatter::formatMoneyTo($report->grandTotalDue),
            'companyName' => (string) ($company->name ?? ''),
            'asOfDate' => $this->asOfDate,
        ];
    }
}
