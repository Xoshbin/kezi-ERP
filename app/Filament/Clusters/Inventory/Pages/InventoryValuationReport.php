<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Services\Inventory\InventoryReportingService;
use Carbon\Carbon;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;

class InventoryValuationReport extends Page
{

    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'filament.clusters.inventory.pages.inventory-valuation-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 20;

    public ?array $data = [];

    public ?array $reportData = null;

    public ?array $reconciliationData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
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

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make(__('inventory_reports.valuation.filters.title'))
                            ->schema([
                                DatePicker::make('as_of_date')
                                    ->label(__('inventory_reports.valuation.filters.as_of_date'))
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                Select::make('product_ids')
                                    ->label(__('inventory_reports.valuation.filters.products'))
                                    ->relationship('products', 'name')
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
                    ->statePath('data')
            ),
        ];
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();
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
            \Filament\Actions\Action::make('export')
                ->label(__('inventory_reports.valuation.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportReport'),

            \Filament\Actions\Action::make('refresh')
                ->label(__('inventory_reports.valuation.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
    }

    public function exportReport(): void
    {
        // TODO: Implement CSV export functionality
        $this->notify('success', __('inventory_reports.valuation.export_started'));
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'reconciliationData' => $this->reconciliationData,
        ];
    }
}
