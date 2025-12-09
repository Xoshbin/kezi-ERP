<?php

namespace Modules\Sales\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SalesPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Sales';
    }

    public function getId(): string
    {
        return 'sales';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
