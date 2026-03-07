<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Pages;

use BackedEnum;
use Brick\Money\Currency;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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

class ReorderStatusReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = InventoryCluster::class;

    protected string $view = 'inventory::filament.clusters.inventory.pages.reorder-status-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 24;

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
        return __('inventory::inventory_reports.reorder.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory::inventory_reports.reorder.title');
    }

    public function getHeading(): string
    {
        return __('inventory::inventory_reports.reorder.heading');
    }

    public function mount(): void
    {
        $this->form->fill([
            'include_suggested_orders' => true,
            'include_overstock' => true,
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('inventory::inventory_reports.reorder.filters.title'))
                    ->schema([
                        \Kezi\Product\Filament\Forms\Components\ProductSelectField::make('product_ids')
                            ->label(__('inventory::inventory_reports.reorder.filters.products'))
                            ->multiple()
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        Select::make('location_ids')
                            ->label(__('inventory::inventory_reports.reorder.filters.locations'))
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

                        Toggle::make('include_suggested_orders')
                            ->label(__('inventory::inventory_reports.reorder.filters.include_suggested_orders'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),

                        Toggle::make('include_overstock')
                            ->label(__('inventory::inventory_reports.reorder.filters.include_overstock'))
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn () => $this->generateReport()),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateReport(): void
    {
        $filters = $this->form->getState();

        $reportingService = $this->getReportingService();

        // Generate reorder status report
        $this->reportData = $reportingService->reorderStatus([
            'product_ids' => $filters['product_ids'] ?? null,
            'location_ids' => $filters['location_ids'] ?? null,
            'include_suggested_orders' => $filters['include_suggested_orders'] ?? false,
            'include_overstock' => $filters['include_overstock'] ?? false,
        ]);
    }

    public function getReordersByStatus(): array
    {
        if (! $this->reportData || empty($this->reportData['products'])) {
            return [
                'critical' => [],
                'low' => [],
                'suggested' => [],
                'overstock' => [],
                'normal' => [],
            ];
        }

        $products = collect($this->reportData['products']);

        return [
            'critical' => $products->where('reorder_status', 'critical')->values()->toArray(),
            'low' => $products->where('reorder_status', 'low')->values()->toArray(),
            'suggested' => $products->where('reorder_status', 'suggested')->values()->toArray(),
            'overstock' => $products->where('reorder_status', 'overstock')->values()->toArray(),
            'normal' => $products->where('reorder_status', 'normal')->values()->toArray(),
        ];
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'critical' => 'danger',
            'low' => 'warning',
            'suggested' => 'info',
            'overstock' => 'purple',
            'normal' => 'success',
            default => 'gray',
        };
    }

    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'critical' => 'heroicon-o-exclamation-triangle',
            'low' => 'heroicon-o-exclamation-circle',
            'suggested' => 'heroicon-o-information-circle',
            'overstock' => 'heroicon-o-arrow-trending-up',
            'normal' => 'heroicon-o-check-circle',
            default => 'heroicon-o-minus-circle',
        };
    }

    public function getTotalSuggestedValue(): Money
    {
        if (! $this->reportData || empty($this->reportData['products'])) {
            return Money::zero(Currency::of('USD'));
        }

        $currency = Currency::of('USD'); // Default
        if (! empty($this->reportData['products'])) {
            $firstProduct = reset($this->reportData['products']);
            if (isset($firstProduct['unit_cost'])) {
                $currency = $firstProduct['unit_cost']->getCurrency();
            }
        }

        $total = Money::zero($currency);
        foreach ($this->reportData['products'] as $product) {
            if ($product['reorder_status'] === 'suggested') {
                $lineTotal = $product['unit_cost']->multipliedBy($product['suggested_quantity'], \Brick\Math\RoundingMode::HALF_UP);
                $total = $total->plus($lineTotal);
            }
        }

        return $total;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('reordering-rules'),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-reports'),
            Action::make('export')
                ->label(__('inventory::inventory_reports.reorder.actions.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->disabled(fn () => ! $this->reportData)
                ->action(function () {
                    if (! $this->reportData) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.reorder.no_data_to_export'))
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        $csvService = app(InventoryCSVExportService::class);
                        $csvContent = $csvService->exportReorderStatusReport($this->reportData, [
                            'include_metadata' => true,
                        ]);

                        $filename = 'inventory-reorder-status-'.now()->format('Y-m-d-H-i-s').'.csv';

                        Notification::make()
                            ->title(__('inventory::inventory_reports.reorder.export_started'))
                            ->success()
                            ->send();

                        return response()->streamDownload(function () use ($csvContent) {
                            echo $csvContent;
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('inventory::inventory_reports.reorder.export_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('refresh')
                ->label(__('inventory::inventory_reports.reorder.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action('generateReport'),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportData' => $this->reportData,
            'reordersByStatus' => $this->getReordersByStatus(),
            'totalSuggestedValue' => $this->getTotalSuggestedValue(),
        ];
    }
}
