<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Pages\Tenancy\EditCompanyProfile;
use App\Filament\Pages\Tenancy\RegisterCompany;
use App\Models\Company;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kezi\Accounting\Filament\AccountingPlugin;
use Kezi\Foundation\Filament\FoundationPlugin;
use Kezi\HR\Filament\HRPlugin;
use Kezi\Inventory\Filament\InventoryPlugin;
use Kezi\Manufacturing\Filament\ManufacturingPlugin;
use Kezi\Payment\Filament\PaymentPlugin;
use Kezi\Pos\Filament\PosPlugin;
use Kezi\Product\Filament\ProductPlugin;
use Kezi\ProjectManagement\Filament\ProjectManagementPlugin;
use Kezi\Purchase\Filament\PurchasePlugin;
use Kezi\QualityControl\Filament\QualityControlPlugin;
use Kezi\Sales\Filament\SalesPlugin;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use Xoshbin\CustomFields\CustomFieldsPlugin;
use Xoshbin\KeziTheme\KeziTheme;

class KeziPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('kezi')
            ->path('kezi')
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate, // Slate provides a modern, cleaner gray scale

            ])
            ->topNavigation()
            ->maxContentWidth('full')
            ->navigation(function (\Filament\Navigation\NavigationBuilder $builder): \Filament\Navigation\NavigationBuilder {
                $panel = \Filament\Facades\Filament::getCurrentPanel();

                // Early return if panel is null
                if ($panel === null) {
                    return $builder;
                }

                $currentUrl = request()->url();

                // Identify the active cluster
                $activeCluster = null;
                foreach ($panel->getClusters() as $cluster) {
                    $clusterUrl = $cluster::getUrl();
                    if (str_starts_with($currentUrl, $clusterUrl)) {
                        $activeCluster = $cluster;
                        break;
                    }
                }

                $groups = [];

                if ($activeCluster) {
                    // Show resources belonging to the active cluster
                    foreach ($panel->getResources() as $resource) {
                        if (! $resource::canAccess()) {
                            continue;
                        }
                        if ($resource::getCluster() !== $activeCluster) {
                            continue;
                        }

                        $groupLabel = $resource::getNavigationGroup() ?? null;

                        if ($resource === 'BezhanSalleh\FilamentShield\Resources\Roles\RoleResource' ||
                            $resource === 'Xoshbin\CustomFields\Filament\Resources\CustomFieldDefinitionResource') {
                            $groupLabel = __('System');
                        }

                        $item = \Filament\Navigation\NavigationItem::make($resource::getNavigationLabel())
                            ->icon($resource::getNavigationIcon() ?? 'heroicon-o-document-text')
                            ->url($resource::getUrl())
                            ->isActiveWhen(fn () => request()->routeIs($resource::getRouteBaseName().'.*'))
                            ->sort($resource::getNavigationSort());

                        if ($groupLabel) {
                            $groups[$groupLabel][] = $item;
                        } else {
                            $groups[''][] = $item;
                        }
                    }

                    // Show pages belonging to the active cluster
                    foreach ($panel->getPages() as $page) {
                        if (! $page::canAccess()) {
                            continue;
                        }
                        if (is_subclass_of($page, \Filament\Clusters\Cluster::class)) {
                            continue;
                        }
                        if ($page::getCluster() !== $activeCluster) {
                            continue;
                        }

                        $groupLabel = $page::getNavigationGroup() ?? null;
                        $item = \Filament\Navigation\NavigationItem::make($page::getNavigationLabel())
                            ->icon($page::getNavigationIcon() ?? 'heroicon-o-document-text')
                            ->url($page::getUrl())
                            ->isActiveWhen(fn () => request()->url() === $page::getUrl())
                            ->sort($page::getNavigationSort());

                        if ($groupLabel) {
                            $groups[$groupLabel][] = $item;
                        } else {
                            $groups[''][] = $item;
                        }
                    }
                } else {
                    // Not in a cluster - show general dashboard and non-clustered items
                    foreach ($panel->getResources() as $resource) {
                        if (! $resource::canAccess()) {
                            continue;
                        }
                        if ($resource::getCluster()) {
                            continue;
                        }

                        foreach ($resource::getNavigationItems() as $item) {
                            $groupLabel = $resource::getNavigationGroup() ?? 'Management';
                            $groups[$groupLabel][] = $item;
                        }
                    }

                    foreach ($panel->getPages() as $page) {
                        if (! $page::canAccess()) {
                            continue;
                        }
                        if (is_subclass_of($page, \Filament\Clusters\Cluster::class)) {
                            continue;
                        }
                        if ($page::getCluster()) {
                            continue;
                        }

                        foreach ($page::getNavigationItems() as $item) {
                            $groupLabel = $page::getNavigationGroup() ?? 'Pages';
                            $groups[$groupLabel][] = $item;
                        }
                    }
                }

                $navGroups = [];

                // Add un-grouped items first
                if (isset($groups[''])) {
                    $navGroups[] = \Filament\Navigation\NavigationGroup::make()->items($groups['']);
                    unset($groups['']);
                }

                foreach ($groups as $label => $items) {
                    /** @var array<\Filament\Navigation\NavigationItem> $items */
                    usort($items, fn ($a, $b) => ($a->getSort() ?? 0) <=> ($b->getSort() ?? 0));
                    $navGroups[] = \Filament\Navigation\NavigationGroup::make((string) $label)->items($items);
                }

                return $builder->groups($navGroups);
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([
                // Widgets\AccountWidget::class
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenantMiddleware([
                \App\Http\Middleware\SetPermissionsTeamId::class,
                \App\Http\Middleware\EnsureOnboardingComplete::class,
            ], isPersistent: true)
            ->brandName('')
            ->globalSearch(false)
            ->viteTheme('resources/css/filament/kezi/theme.css')
            ->tenant(Company::class)
            ->tenantRegistration(RegisterCompany::class)
            ->tenantProfile(EditCompanyProfile::class)
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => Blade::render('<x-app-switcher />')
            )
            ->plugins([
                // KeziTheme::make(),
                FoundationPlugin::make(),
                AccountingPlugin::make(),
                HRPlugin::make(),
                InventoryPlugin::make(),
                ManufacturingPlugin::make(),
                PaymentPlugin::make(),
                ProductPlugin::make(),
                ProjectManagementPlugin::make(),
                PurchasePlugin::make(),
                QualityControlPlugin::make(),
                SalesPlugin::make(),
                PosPlugin::make(),
                FilamentLanguageSwitcherPlugin::make()
                    ->locales([
                        ['code' => 'ckb', 'name' => 'کوردی'],
                        ['code' => 'en', 'name' => 'English'],
                        ['code' => 'ar', 'name' => 'العربية'],
                    ])
                    ->showFlags(false),
                EnvironmentIndicatorPlugin::make()
                    ->showBadge(false)
                    ->showBorder(true),
                CustomFieldsPlugin::make()
                    ->modelTypes([
                        \Kezi\Foundation\Models\Partner::class => __('Partners'),
                        \Kezi\Product\Models\Product::class => __('Products'),
                        \Kezi\HR\Models\Employee::class => __('Employees'),
                        \Kezi\HR\Models\Department::class => __('Departments'),
                        \Kezi\HR\Models\Position::class => __('Positions'),
                        \Kezi\Accounting\Models\Asset::class => __('Assets'),
                    ])
                    ->cluster(SettingsCluster::class)
                    ->navigationSort(5),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'ckb', 'ar']),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'admin@kezi.com',
                    ]),
                FilamentShieldPlugin::make(),
            ]);
    }
}
