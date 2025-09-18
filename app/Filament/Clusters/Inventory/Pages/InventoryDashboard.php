<?php

namespace App\Filament\Clusters\Inventory\Pages;

use App\Filament\Clusters\Inventory\InventoryCluster;
use Filament\Pages\Dashboard;

class InventoryDashboard extends Dashboard
{
    protected static ?string $cluster = InventoryCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('inventory_dashboard.navigation_label');
    }

    public function getHeading(): string
    {
        return __('inventory_dashboard.heading');
    }

    public function getSubheading(): ?string
    {
        return __('inventory_dashboard.subheading');
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

    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
