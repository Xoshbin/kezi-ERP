<?php

namespace Modules\Accounting\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AccountingPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Accounting';
    }

    public function getId(): string
    {
        return 'accounting';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
