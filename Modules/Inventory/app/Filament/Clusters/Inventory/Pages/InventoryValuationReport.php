<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Pages;

use Exception;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Modules\Product\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Modules\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Modules\Inventory\Services\Inventory\InventoryCSVExportService;
use Modules\Inventory\Services\Inventory\InventoryReportingService;

class InventoryValuationReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'filament.clusters.inventory.pages.inventory-valuation-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public ?array $reportData = null;

    public ?array $reconciliationData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    protected function isValidDate($date): bool
    {
        try {
            Carbon::parse($date);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory_reports.valuation.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory_reports.valuation.title');
    }

    public function getHeading(): string
    {
        return __('inventory_reports.valuation.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'as_of_date' => now()->format('Y-m-d'),
            'include_reconciliation' => true,
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory_reports.valuation.filters.title'))
                    ->schema([
                        DatePicker::make('as_of_date')
                            ->label(__('inventory_reports.valuation.filters.as_of_date'))
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                // Only generate report if the date is valid
                                if ($state && $this->isValidDate($state)) {
                                    $this->generateReport();
                                }
                            }),

                        Select::make('product_ids')
                            ->label(__('inventory_reports.valuation.filters.products'))
                            ->options(function () {
                                return Product::query()
                                    ->where('company_id', Filament::getTenant()?->getKey())
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn() => $this->generateReport()),

                        Toggle::make('include_reconciliation')
                            ->label(__('inventory_reports.valuation.filters.include_reconciliation'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn() => $this->generateReport()),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();

        // Skip report generation if required fields are missing
        if (empty($filters['as_of_date'])) {
            return;
        }

        $asOfDate = Carbon::parse($filters['as_of_date']);

        $reportingService = $this->getReportingService();

        // Generate valuation report
        $this->reportData = $reportingService->valuationAt($asOfDate, [
            'product_ids' => $filters['product_ids'] ?? null,
        ]);

        // Generate reconciliation if requested
        if ($filters['include_reconciliation']) {
            $this->reconciliationData = $reportingService->reconcileWithGL($asOfDate, [
                'product_ids' => $filters['product_ids'] ?? null,
            ]);
        } else {
            $this->reconciliationData = null;
        }
    }

    public function getProductDetails(): array
    {
        if (!$this->reportData || empty($this->reportData['by_product'])) {
            return [];
        }

        $products = [];
        foreach ($this->reportData['by_product'] as $productData) {
            $unitCost = $productData['quantity'] > 0
                ? $productData['value']->getAmount()->toFloat() / $productData['quantity']
                : 0;

            $products[] = [
                'product_name' => $productData['product_name'],
                'valuation_method' => $productData['valuation_method']->value,
                'quantity' => $productData['quantity'],
                'unit_cost' => $unitCost,
                'total_value' => $productData['value'],
                'cost_layers' => $productData['cost_layers'] ?? [],
                'cost_layers_count' => count($productData['cost_layers'] ?? []),
            ];
        }

        return $products;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('inventory_reports.valuation.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->disabled(fn() => !$this->reportData)
                ->requiresConfirmation()
                ->modalHeading(__('inventory_reports.valuation.export_confirmation'))
                ->modalDescription(__('inventory_reports.valuation.export_description'))
                ->modalSubmitActionLabel(__('inventory_reports.valuation.actions.export'))
                ->action(function () {
                    if (!$this->reportData) {
                        Notification::make()
                            ->title(__('inventory_reports.valuation.no_data_to_export'))
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        $csvService = app(InventoryCSVExportService::class);
                        $csvContent = $csvService->exportValuationReport($this->reportData, [
                            'include_metadata' => true,
                        ]);

                        $filename = 'inventory-valuation-' . now()->format('Y-m-d-H-i-s') . '.csv';

                        Notification::make()
                            ->title(__('inventory_reports.valuation.export_started'))
                            ->success()
                            ->send();

                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('inventory_reports.valuation.export_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label(__('inventory_reports.valuation.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'reconciliationData' => $this->reconciliationData,
        ];
    }
}
