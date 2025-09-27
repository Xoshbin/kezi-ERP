<?php

namespace Modules\Product\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ProductPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Product';
    }

    public function getId(): string
    {
        return 'product';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
