<?php

namespace Xoshbin\JmeryarTheme;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Theme;
use Filament\Support\Color;
use Filament\Support\Facades\FilamentAsset;

class JmeryarTheme implements Plugin
{
    public function getId(): string
    {
        return 'jmeryar-theme';
    }

    public static function make(): static
    {
        return new static;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->viteTheme('vendor/xoshbin/jmeryar-theme/resources/css/theme.css');
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
