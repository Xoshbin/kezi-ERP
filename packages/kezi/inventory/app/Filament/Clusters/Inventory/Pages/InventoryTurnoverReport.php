<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Pages;

use BackedEnum;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Services\Inventory\InventoryCSVExportService;
use Kezi\Inventory\Services\Inventory\InventoryReportingService;
use Kezi\Product\Models\Product;

class InventoryTurnoverReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'inventory::filament.clusters.inventory.pages.inventory-turnover-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 22;

    public ?array $data = [];

    public ?array $reportData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory::inventory_reports.turnover.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory::inventory_reports.turnover.title');
    }

    public function getHeading(): string
    {
        return __('inventory::inventory_reports.turnover.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory::inventory_reports.turnover.filters.title'))
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('inventory::inventory_reports.turnover.filters.start_date'))
                            ->required()
                            ->default(now()->startOfYear())
                            ->maxDate(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        DatePicker::make('end_date')
                            ->label(__('inventory::inventory_reports.turnover.filters.end_date'))
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        Select::make('product_ids')
                            ->label(__('inventory::inventory_reports.turnover.filters.products'))
                            ->options(function () {
                                return Product::query()
                                    ->where('company_id', Filament::getTenant()?->getKey())
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();

        // Skip report generation if required fields are missing
        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            return;
        }

        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);

        $reportingService = $this->getReportingService();

        // Generate turnover report
        $this->reportData = $reportingService->turnover([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'product_ids' => $filters['product_ids'] ?? null,
        ]);
    }

    public function getTurnoverAnalysis(): array
    {
        if (! $this->reportData) {
            return [];
        }

        $ratio = $this->reportData['inventory_turnover_ratio'];

        if ($ratio >= 12) {
            return [
                'level' => 'excellent',
                'color' => 'success',
                'description' => __('inventory::inventory_reports.turnover.analysis.excellent'),
            ];
        } elseif ($ratio >= 6) {
            return [
                'level' => 'good',
                'color' => 'info',
                'description' => __('inventory::inventory_reports.turnover.analysis.good'),
            ];
        } elseif ($ratio >= 3) {
            return [
                'level' => 'average',
                'color' => 'warning',
                'description' => __('inventory::inventory_reports.turnover.analysis.average'),
            ];
        } else {
            return [
                'level' => 'poor',
                'color' => 'danger',
                'description' => __('inventory::inventory_reports.turnover.analysis.poor'),
            ];
        }
    }

    public function getPeriodLength(): int
    {
        if (! $this->reportData) {
            return 0;
        }

        return $this->reportData['period_start']->diffInDays($this->reportData['period_end']);
    }

    public function getAnnualizedTurnover(): float
    {
        if (! $this->reportData) {
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
            Action::make('export')
                ->label(__('inventory::inventory_reports.turnover.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->disabled(fn () => ! $this->reportData)
                ->action(function () {
                    if (! $this->reportData) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.turnover.no_data_to_export'))
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $csvService = app(InventoryCSVExportService::class);
                        $csvContent = $csvService->exportTurnoverReport($this->reportData, [
                            'include_metadata' => true,
                        ]);

                        $filename = 'inventory-turnover-'.now()->format('Y-m-d-H-i-s').'.csv';

                        Notification::make()
                            ->title(__('inventory::inventory_reports.turnover.export_started'))
                            ->success()
                            ->send();

                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.turnover.export_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-reports'),
            Action::make('refresh')
                ->label(__('inventory::inventory_reports.turnover.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
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
