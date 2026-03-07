<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Pages;

use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Kezi\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Services\Inventory\InventoryCSVExportService;
use Kezi\Inventory\Services\Inventory\InventoryReportingService;

class InventoryAgingReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'inventory::filament.clusters.inventory.pages.inventory-aging-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): ?string
    {
        return __('Reporting');
    }

    public ?array $data = [];

    public ?array $reportData = null;

    protected function getReportingService(): InventoryReportingService
    {
        return app(InventoryReportingService::class);
    }

    public static function getNavigationLabel(): string
    {
        return __('inventory::inventory_reports.aging.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory::inventory_reports.aging.title');
    }

    public function getHeading(): string
    {
        return __('inventory::inventory_reports.aging.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'include_expiration' => true,
            'expiration_warning_days' => 30,
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory::inventory_reports.aging.filters.title'))
                    ->schema([
                        \Kezi\Product\Filament\Forms\Components\ProductSelectField::make('product_ids')
                            ->label(__('inventory::inventory_reports.aging.filters.products'))
                            ->multiple()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        Select::make('location_ids')
                            ->label(__('inventory::inventory_reports.aging.filters.locations'))
                            ->options(function () {
                                return StockLocation::query()
                                    ->where('company_id', Filament::getTenant()?->getKey())
                                    ->pluck('name', 'id');
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        Toggle::make('include_expiration')
                            ->label(__('inventory::inventory_reports.aging.filters.include_expiration'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        TextInput::make('expiration_warning_days')
                            ->label(__('inventory::inventory_reports.aging.filters.expiration_warning_days'))
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport())
                            ->visible(fn ($get) => $get('include_expiration')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
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
        if (! $this->reportData || empty($this->reportData['buckets'])) {
            return [];
        }

        $buckets = [];
        $totalValue = $this->reportData['total_value'];
        $totalQuantity = $this->reportData['total_quantity'];

        foreach ($this->reportData['buckets'] as $label => $bucket) {
            $valuePercentage = $totalValue->isZero() ? 0 : ($bucket['value']->getAmount()->toFloat() / $totalValue->getAmount()->toFloat()) * 100;

            $quantityPercentage = $totalQuantity == 0 ? 0 : ($bucket['quantity'] / $totalQuantity) * 100;

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
        if (! $this->reportData || ! isset($this->reportData['expiring_soon'])) {
            return [];
        }

        return $this->reportData['expiring_soon'];
    }

    public function getAverageAge(): float
    {
        if (! $this->reportData || empty($this->reportData['buckets'])) {
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
            Action::make('export')
                ->label(__('inventory::inventory_reports.aging.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->disabled(fn () => ! $this->reportData)
                ->requiresConfirmation()
                ->modalHeading(__('inventory::inventory_reports.aging.export_confirmation'))
                ->modalDescription(__('inventory::inventory_reports.aging.export_description'))
                ->modalSubmitActionLabel(__('inventory::inventory_reports.aging.actions.export'))
                ->action(function () {
                    if (! $this->reportData) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.aging.no_data_to_export'))
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $csvService = app(InventoryCSVExportService::class);
                        $csvContent = $csvService->exportAgingReport($this->reportData, [
                            'include_metadata' => true,
                        ]);

                        $filename = 'inventory-aging-'.now()->format('Y-m-d-H-i-s').'.csv';

                        Notification::make()
                            ->title(__('inventory::inventory_reports.aging.export_started'))
                            ->success()
                            ->send();

                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.aging.export_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-reports'),
            Action::make('refresh')
                ->label(__('inventory::inventory_reports.aging.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
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
