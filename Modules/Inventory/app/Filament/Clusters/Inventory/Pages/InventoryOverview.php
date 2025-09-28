<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Modules\Inventory\Filament\Clusters\Inventory\InventoryCluster;
use Modules\Inventory\Filament\Clusters\Inventory\Widgets\InventoryAgingChartWidget;
use Modules\Inventory\Filament\Clusters\Inventory\Widgets\InventoryStatsOverviewWidget;
use Modules\Inventory\Filament\Clusters\Inventory\Widgets\InventoryTurnoverChartWidget;
use Modules\Inventory\Filament\Clusters\Inventory\Widgets\InventoryValueChartWidget;

class InventoryOverview extends Page
{
    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.clusters.inventory.pages.inventory-overview';

    public static function getNavigationLabel(): string
    {
        return __('inventory_dashboard.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory_dashboard.heading');
    }

    public function getHeading(): string
    {
        return __('inventory_dashboard.heading');
    }

    public function getSubheading(): ?string
    {
        return __('inventory_dashboard.subheading');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryStatsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            InventoryValueChartWidget::class,
            InventoryTurnoverChartWidget::class,
            InventoryAgingChartWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            InventoryStatsOverviewWidget::class,
            InventoryValueChartWidget::class,
            InventoryTurnoverChartWidget::class,
            InventoryAgingChartWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
