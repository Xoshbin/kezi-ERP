<?php

namespace Modules\Purchase\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class PurchasePlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Purchase';
    }

    public function getId(): string
    {
        return 'purchase';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
