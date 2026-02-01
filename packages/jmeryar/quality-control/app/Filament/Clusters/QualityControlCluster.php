<?php

namespace Jmeryar\QualityControl\Filament\Clusters;

use Filament\Clusters\Cluster;

class QualityControlCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 50;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('qualitycontrol::cluster.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('qualitycontrol::cluster.group');
    }
}
