<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Widgets;
use App\Models\Company;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Xoshbin\JmeryarTheme\JmeryarTheme;
use Filament\Http\Middleware\Authenticate;
use Xoshbin\CustomFields\CustomFieldsPlugin;
use App\Filament\Pages\Tenancy\RegisterCompany;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Pages\Tenancy\EditCompanyProfile;
use Xoshbin\FilamentAiHelper\FilamentAiHelperPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use LaraZeus\SpatieTranslatable\SpatieTranslatablePlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;

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
                // JmeryarTheme::make(),
                CustomFieldsPlugin::make(),
                SpatieTranslatablePlugin::make()
                    ->defaultLocales(['en', 'ckb', 'ar']),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->users([
                        'Admin' => 'admin@jmeryar.com',
                    ])
                // FilamentAiHelperPlugin::make()
                //     ->buttonLabel('AccounTech Pro')
                //     ->buttonIcon('heroicon-o-sparkles')
                //     ->modalWidth('2xl')
                //     ->enabled(fn () => (bool) config('filament-ai-helper.enabled', true) && !empty(config('filament-ai-helper.gemini.api_key'))),
            ]);
    }
}
