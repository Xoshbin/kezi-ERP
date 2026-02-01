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
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Jmeryar\Accounting\Filament\AccountingPlugin;
use Jmeryar\Foundation\Filament\FoundationPlugin;
use Jmeryar\HR\Filament\HRPlugin;
use Jmeryar\Inventory\Filament\InventoryPlugin;
use Jmeryar\Manufacturing\Filament\ManufacturingPlugin;
use Jmeryar\Payment\Filament\PaymentPlugin;
use Jmeryar\Product\Filament\ProductPlugin;
use Jmeryar\ProjectManagement\Filament\ProjectManagementPlugin;
use Jmeryar\Purchase\Filament\PurchasePlugin;
use Jmeryar\QualityControl\Filament\QualityControlPlugin;
use Jmeryar\Sales\Filament\SalesPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use Xoshbin\CustomFields\CustomFieldsPlugin;
use Xoshbin\FilamentAiHelper\FilamentAiHelperPlugin;
use Xoshbin\JmeryarTheme\JmeryarTheme;

class JmeryarPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('jmeryar')
            ->path('jmeryar')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate, // Slate provides a modern, cleaner gray scale

            ])
            ->topNavigation()
            ->maxContentWidth('full')
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
            ], isPersistent: true)
            ->brandName('')
            ->globalSearch(false)
            ->viteTheme('resources/js/filament/jmeryar/theme.js')
            ->tenant(Company::class)
            ->tenantRegistration(RegisterCompany::class)
            ->tenantProfile(EditCompanyProfile::class)
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => Blade::render('<x-app-switcher />')
            )
            ->plugins([
                // JmeryarTheme::make(),
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
                        \Jmeryar\Foundation\Models\Partner::class => __('Partners'),
                        \Jmeryar\Product\Models\Product::class => __('Products'),
                        \Jmeryar\HR\Models\Employee::class => __('Employees'),
                        \Jmeryar\HR\Models\Department::class => __('Departments'),
                        \Jmeryar\HR\Models\Position::class => __('Positions'),
                        \Jmeryar\Accounting\Models\Asset::class => __('Assets'),
                    ])
                    ->cluster(SettingsCluster::class)
                    ->navigationSort(5),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'ckb', 'ar']),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'admin@jmeryar.com',
                    ]),
                FilamentShieldPlugin::make(),
                // FilamentAiHelperPlugin::make()
                //     ->buttonLabel('AccounTech Pro')
                //     ->buttonIcon('heroicon-o-sparkles')
                //     ->modalWidth('2xl')
                //     ->enabled(fn () => (bool) config('filament-ai-helper.enabled', true) && !empty(config('filament-ai-helper.gemini.api_key'))),
            ]);
    }
}
