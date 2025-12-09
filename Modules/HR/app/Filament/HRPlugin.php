<?php

namespace Modules\HR\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class HRPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'HR';
    }

    public function getId(): string
    {
        return 'hr';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
