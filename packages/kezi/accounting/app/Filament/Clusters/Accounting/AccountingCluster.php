<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting;

use BackedEnum;
use Filament\Clusters\Cluster;

class AccountingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    public static function getNavigationLabel(): string
    {
        return __('accounting::navigation.clusters.accounting');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        return Pages\Dashboard::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
}
