<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Services\Inventory\InventoryReportingService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;

class InventoryTurnoverReport extends Page
{
    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'filament.clusters.inventory.pages.inventory-turnover-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 22;

    public ?array $data = [];

    public ?array $reportData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory_reports.turnover.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory_reports.turnover.title');
    }

    public function getHeading(): string
    {
        return __('inventory_reports.turnover.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $this->generateReport();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make(__('inventory_reports.turnover.filters.title'))
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label(__('inventory_reports.turnover.filters.start_date'))
                                    ->required()
                                    ->default(now()->startOfYear())
                                    ->maxDate(now())
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                DatePicker::make('end_date')
                                    ->label(__('inventory_reports.turnover.filters.end_date'))
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                Select::make('product_ids')
                                    ->label(__('inventory_reports.turnover.filters.products'))
                                    ->relationship('products', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),
                            ])
                            ->columns(3),
                    ])
                    ->statePath('data')
            ),
        ];
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();

        $reportingService = $this->getReportingService();

        // Generate turnover report
        $this->reportData = $reportingService->turnover([
            'start_date' => Carbon::parse($filters['start_date']),
            'end_date' => Carbon::parse($filters['end_date']),
            'product_ids' => $filters['product_ids'] ?? null,
        ]);
    }

    public function getTurnoverAnalysis(): array
    {
        if (!$this->reportData) {
            return [];
        }

        $ratio = $this->reportData['inventory_turnover_ratio'];

        if ($ratio >= 12) {
            return [
                'level' => 'excellent',
                'color' => 'success',
                'description' => __('inventory_reports.turnover.analysis.excellent'),
            ];
        } elseif ($ratio >= 6) {
            return [
                'level' => 'good',
                'color' => 'info',
                'description' => __('inventory_reports.turnover.analysis.good'),
            ];
        } elseif ($ratio >= 3) {
            return [
                'level' => 'average',
                'color' => 'warning',
                'description' => __('inventory_reports.turnover.analysis.average'),
            ];
        } else {
            return [
                'level' => 'poor',
                'color' => 'danger',
                'description' => __('inventory_reports.turnover.analysis.poor'),
            ];
        }
    }

    public function getPeriodLength(): int
    {
        if (!$this->reportData) {
            return 0;
        }

        return $this->reportData['period_start']->diffInDays($this->reportData['period_end']);
    }

    public function getAnnualizedTurnover(): float
    {
        if (!$this->reportData) {
            return 0;
        }

        $periodDays = $this->getPeriodLength();
        if ($periodDays == 0) {
            return 0;
        }

        $ratio = $this->reportData['inventory_turnover_ratio'];
        return ($ratio * 365) / $periodDays;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label(__('inventory_reports.turnover.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportReport'),

            \Filament\Actions\Action::make('refresh')
                ->label(__('inventory_reports.turnover.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
    }

    public function exportReport(): void
    {
        // TODO: Implement CSV export functionality
        $this->notify('success', __('inventory_reports.turnover.export_started'));
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'turnoverAnalysis' => $this->getTurnoverAnalysis(),
            'periodLength' => $this->getPeriodLength(),
            'annualizedTurnover' => $this->getAnnualizedTurnover(),
        ];
    }
}
