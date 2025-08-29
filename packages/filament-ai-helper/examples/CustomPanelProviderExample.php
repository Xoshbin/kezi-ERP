<?php

namespace App\Providers\Filament;

use AccounTech\FilamentAiHelper\FilamentAiHelperPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->plugins([
                // Register the AI Helper plugin with custom configuration
                FilamentAiHelperPlugin::make()
                    ->buttonLabel('AI Assistant')
                    ->buttonIcon('heroicon-o-sparkles')
                    ->modalWidth('xl')
                    ->enabled(fn () => auth()->user()?->can('use-ai-assistant') ?? false),
            ]);
    }
}

// Alternative: Minimal configuration
class MinimalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->plugins([
                // Simple registration with default settings
                FilamentAiHelperPlugin::make(),
            ]);
    }
}

// Example: Multiple panels with different AI configurations
class AccountingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('accounting')
            ->path('accounting')
            ->login()
            ->plugins([
                FilamentAiHelperPlugin::make()
                    ->buttonLabel('AccounTech Pro')
                    ->buttonIcon('heroicon-o-calculator')
                    ->modalWidth('2xl')
                    ->enabled(true), // Always enabled for accounting panel
            ]);
    }
}

class SalesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('sales')
            ->path('sales')
            ->login()
            ->plugins([
                FilamentAiHelperPlugin::make()
                    ->buttonLabel('Sales AI')
                    ->buttonIcon('heroicon-o-chart-bar')
                    ->modalWidth('lg')
                    ->enabled(fn () => config('app.env') !== 'production'), // Only in non-production
            ]);
    }
}
