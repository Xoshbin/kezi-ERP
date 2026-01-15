<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Modules\Inventory\Filament\Clusters\Inventory\InventoryCluster;

class InventoryOverview extends Page
{
    protected static ?string $cluster = InventoryCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 10;

    protected string $view = 'inventory::filament.clusters.inventory.pages.inventory-overview';

    public static function getNavigationLabel(): string
    {
        return __('inventory::inventory_dashboard.navigation_label');
    }

    public function getTitle(): string
    {
        return __('inventory::inventory_dashboard.heading');
    }

    public function getHeading(): string
    {
        return __('inventory::inventory_dashboard.heading');
    }

    public function getSubheading(): ?string
    {
        return __('inventory::inventory_dashboard.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('inventory-reports'),
        ];
    }
}
