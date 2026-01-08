<?php

namespace Modules\Manufacturing\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ManufacturingPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Manufacturing';
    }

    public function getId(): string
    {
        return 'manufacturing';
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
