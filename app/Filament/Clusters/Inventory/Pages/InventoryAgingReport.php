<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use App\Services\Inventory\InventoryReportingService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;

class InventoryAgingReport extends Page
{
    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'filament.clusters.inventory.pages.inventory-aging-report';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 21;

    public ?array $data = [];

    public ?array $reportData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory_reports.aging.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory_reports.aging.title');
    }

    public function getHeading(): string
    {
        return __('inventory_reports.aging.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'include_expiration' => true,
            'expiration_warning_days' => 30,
        ]);

        $this->generateReport();
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make(__('inventory_reports.aging.filters.title'))
                            ->schema([
                                Select::make('product_ids')
                                    ->label(__('inventory_reports.aging.filters.products'))
                                    ->relationship('products', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                Select::make('location_ids')
                                    ->label(__('inventory_reports.aging.filters.locations'))
                                    ->relationship('locations', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                Toggle::make('include_expiration')
                                    ->label(__('inventory_reports.aging.filters.include_expiration'))
                                    ->default(true)
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport()),

                                TextInput::make('expiration_warning_days')
                                    ->label(__('inventory_reports.aging.filters.expiration_warning_days'))
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->generateReport())
                                    ->visible(fn($get) => $get('include_expiration')),
                            ])
                            ->columns(2),
                    ])
                    ->statePath('data')
            ),
        ];
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();

        $reportingService = $this->getReportingService();

        // Generate aging report
        $this->reportData = $reportingService->ageing([
            'product_ids' => $filters['product_ids'] ?? null,
            'location_ids' => $filters['location_ids'] ?? null,
            'include_expiration' => $filters['include_expiration'] ?? false,
            'expiration_warning_days' => $filters['expiration_warning_days'] ?? 30,
        ]);
    }

    public function getBucketData(): array
    {
        if (!$this->reportData || empty($this->reportData['buckets'])) {
            return [];
        }

        $buckets = [];
        $totalValue = $this->reportData['total_value'];
        $totalQuantity = $this->reportData['total_quantity'];

        foreach ($this->reportData['buckets'] as $label => $bucket) {
            $valuePercentage = $totalValue->isZero() ? 0 : 
                ($bucket['value']->getAmount()->toFloat() / $totalValue->getAmount()->toFloat()) * 100;
            
            $quantityPercentage = $totalQuantity == 0 ? 0 : 
                ($bucket['quantity'] / $totalQuantity) * 100;

            $buckets[] = [
                'label' => $label,
                'quantity' => $bucket['quantity'],
                'value' => $bucket['value'],
                'value_percentage' => $valuePercentage,
                'quantity_percentage' => $quantityPercentage,
                'product_count' => count($bucket['products'] ?? []),
            ];
        }

        return $buckets;
    }

    public function getExpiringLots(): array
    {
        if (!$this->reportData || !isset($this->reportData['expiring_soon'])) {
            return [];
        }

        return $this->reportData['expiring_soon'];
    }

    public function getAverageAge(): float
    {
        if (!$this->reportData || empty($this->reportData['buckets'])) {
            return 0;
        }

        $totalWeightedAge = 0;
        $totalQuantity = $this->reportData['total_quantity'];

        foreach ($this->reportData['buckets'] as $label => $bucket) {
            // Extract age from bucket label (e.g., "0-30 days" -> 15 days average)
            if (preg_match('/(\d+)-(\d+)/', $label, $matches)) {
                $minAge = (int) $matches[1];
                $maxAge = (int) $matches[2];
                $avgAge = ($minAge + $maxAge) / 2;
            } elseif (preg_match('/(\d+)\+/', $label, $matches)) {
                $avgAge = (int) $matches[1] + 90; // Assume 90 days beyond minimum for "180+" buckets
            } else {
                $avgAge = 0;
            }

            $totalWeightedAge += $avgAge * $bucket['quantity'];
        }

        return $totalQuantity > 0 ? $totalWeightedAge / $totalQuantity : 0;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('export')
                ->label(__('inventory_reports.aging.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportReport'),

            \Filament\Actions\Action::make('refresh')
                ->label(__('inventory_reports.aging.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
    }

    public function exportReport(): void
    {
        // TODO: Implement CSV export functionality
        $this->notify('success', __('inventory_reports.aging.export_started'));
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'bucketData' => $this->getBucketData(),
            'expiringLots' => $this->getExpiringLots(),
            'averageAge' => $this->getAverageAge(),
        ];
    }
}
