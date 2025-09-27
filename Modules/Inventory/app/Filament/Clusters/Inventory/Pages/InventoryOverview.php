<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use Filament\Pages\Page;

class InventoryOverview extends Page
{
    protected static ?string $cluster = InventoryCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

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
            \App\Filament\Clusters\Inventory\Widgets\InventoryStatsOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Clusters\Inventory\Widgets\InventoryValueChartWidget::class,
            \App\Filament\Clusters\Inventory\Widgets\InventoryTurnoverChartWidget::class,
            \App\Filament\Clusters\Inventory\Widgets\InventoryAgingChartWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Clusters\Inventory\Widgets\InventoryStatsOverviewWidget::class,
            \App\Filament\Clusters\Inventory\Widgets\InventoryValueChartWidget::class,
            \App\Filament\Clusters\Inventory\Widgets\InventoryTurnoverChartWidget::class,
            \App\Filament\Clusters\Inventory\Widgets\InventoryAgingChartWidget::class,
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
