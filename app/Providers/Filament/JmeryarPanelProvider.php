<?php

namespace App\Providers\Filament;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Pages\Tenancy\EditCompanyProfile;
use App\Filament\Pages\Tenancy\RegisterCompany;
use App\Models\Company;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Coolsam\Modules\ModulesPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
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
            ])
            ->topNavigation()
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverResources(in: base_path('Modules/Foundation/app/Filament/Resources'), for: 'Modules\\Foundation\\Filament\\Resources')
            ->discoverClusters(in: base_path('Modules/Manufacturing/app/Filament/Clusters'), for: 'Modules\\Manufacturing\\Filament\\Clusters')
            ->discoverResources(in: base_path('Modules/ProjectManagement/app/Filament/Clusters/ProjectManagement/Resources'), for: 'Modules\\ProjectManagement\\Filament\\Clusters\\ProjectManagement\\Resources')
            ->discoverResources(in: base_path('Modules/QualityControl/app/Filament/Clusters/QualityControl/Resources'), for: 'Modules\\QualityControl\\Filament\\Clusters\\QualityControl\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverPages(in: base_path('Modules/ProjectManagement/app/Filament/Clusters/ProjectManagement/Pages'), for: 'Modules\\ProjectManagement\\Filament\\Clusters\\ProjectManagement\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverClusters(in: base_path('Modules/ProjectManagement/app/Filament/Clusters'), for: 'Modules\\ProjectManagement\\Filament\\Clusters')
            ->discoverClusters(in: base_path('Modules/QualityControl/app/Filament/Clusters'), for: 'Modules\\QualityControl\\Filament\\Clusters')
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
                ModulesPlugin::make(),
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
                        \Modules\Foundation\Models\Partner::class => __('Partners'),
                        \Modules\Product\Models\Product::class => __('Products'),
                        \Modules\HR\Models\Employee::class => __('Employees'),
                        \Modules\HR\Models\Department::class => __('Departments'),
                        \Modules\HR\Models\Position::class => __('Positions'),
                        \Modules\Accounting\Models\Asset::class => __('Assets'),
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
