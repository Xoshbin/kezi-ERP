<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Pages\Reports;

use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Kezi\Accounting\Filament\Clusters\Accounting\AccountingCluster;
use Kezi\Foundation\Filament\Actions\DocsAction;

class ViewAnalyticReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'accounting::filament.pages.reports.view-analytic-report';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounting::navigation.groups.reports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<mixed> */
    public ?array $analyticPlans = [];

    /** @var array<string, mixed>|null */
    public ?array $reportData = null;

    public static function getNavigationLabel(): string
    {
        return __('accounting::reports.analytic_report');
    }

    public function getTitle(): string|Htmlable
    {
        return __('accounting::reports.analytic_report');
    }

    public function getHeading(): string|Htmlable
    {
        return __('accounting::reports.analytic_report');
    }

    public function mount(): void
    {
        $this->getSchema('form')?->fill([
            'startDate' => Carbon::now()->startOfMonth()->toDateString(),
            'endDate' => Carbon::now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('accounting::reports.report_parameters'))
                    ->schema([
                        DatePicker::make('startDate')
                            ->label(__('accounting::reports.start_date'))
                            ->required(),
                        DatePicker::make('endDate')
                            ->label(__('accounting::reports.end_date'))
                            ->required(),
                        Select::make('analyticPlans')
                            ->label(__('accounting::reports.analytic_plans'))
                            ->multiple()
                            ->options([]) // Placeholder
                            ->placeholder(__('accounting::reports.select_analytic_plans')),
                    ])->columns(3),
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
            DocsAction::make('analytic-report'),
        ];
    }

    public function generateReport(): void
    {
        $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        // Placeholder for report generation logic
        $this->reportData = [
            'message' => 'Report generation logic to be implemented.',
        ];
    }
}
