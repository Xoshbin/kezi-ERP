<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Tenancy\EditCompanyProfile;
use App\Filament\Pages\Tenancy\RegisterCompany;
use App\Models\Company;
use Coolsam\Modules\ModulesPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Xoshbin\CustomFields\CustomFieldsPlugin;
use Xoshbin\JmeryarTheme\JmeryarTheme;
use Modules\Foundation\Filament\Resources\CurrencyRates\CurrencyRateResource;
use Modules\Foundation\Filament\Resources\PdfSettings\PdfSettingsResource;

class JmeryarPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Base panel configuration
        $panel = $panel
            ->default()
            ->id('jmeryar')
            ->path('jmeryar')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->topNavigation()
            ->maxContentWidth('full')
            // App-level discovery
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
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
            ->viteTheme('resources/js/filament/jmeryar/theme.js')
            ->tenant(Company::class)
            ->tenantRegistration(RegisterCompany::class)
            ->tenantProfile(EditCompanyProfile::class)
            ->plugins([
                ModulesPlugin::make(),
                // JmeryarTheme::make(),
                CustomFieldsPlugin::make(),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'ckb', 'ar']),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'admin@jmeryar.com',
                    ]),
                // FilamentAiHelperPlugin::make()
                //     ->buttonLabel('AccounTech Pro')
                //     ->buttonIcon('heroicon-o-sparkles')
                //     ->modalWidth('2xl')
                //     ->enabled(fn () => (bool) config('filament-ai-helper.enabled', true) && !empty(config('filament-ai-helper.gemini.api_key'))),
            ]);

        // Dynamically discover Filament classes from enabled Modules
        $modulesBase = base_path('Modules');
        foreach (glob($modulesBase . '/*/app/Filament/Resources', GLOB_ONLYDIR) as $resourcesDir) {
            $moduleName = basename(dirname(dirname($resourcesDir))); // Modules/{Module}/app/Filament/Resources
            $namespace = 'Modules\\' . $moduleName . '\\Filament\\Resources';
            $panel = $panel->discoverResources(in: $resourcesDir, for: $namespace);
        }
        foreach (glob($modulesBase . '/*/app/Filament/Pages', GLOB_ONLYDIR) as $pagesDir) {
            $moduleName = basename(dirname(dirname($pagesDir)));
            $namespace = 'Modules\\' . $moduleName . '\\Filament\\Pages';
            $panel = $panel->discoverPages(in: $pagesDir, for: $namespace);
        }
        foreach (glob($modulesBase . '/*/app/Filament/Widgets', GLOB_ONLYDIR) as $widgetsDir) {
            $moduleName = basename(dirname(dirname($widgetsDir)));
            $namespace = 'Modules\\' . $moduleName . '\\Filament\\Widgets';
            $panel = $panel->discoverWidgets(in: $widgetsDir, for: $namespace);
        }

        // Explicitly register critical Foundation resources to ensure routes exist during tests
        $panel = $panel->resources([
            CurrencyRateResource::class,
            PdfSettingsResource::class,
        ]);

        return $panel;
    }
}
